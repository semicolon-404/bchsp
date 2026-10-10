<?php

namespace Tests\Feature;

use App\Filament\Resources\Banners\Pages\ManageBanners;
use App\Models\Banner;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BannersAndPostersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The home page is kept short: its pictures show there once an administrator switches them on.
        $this->showOnHome('slides', 'posters');
    }

    public function test_the_pictures_are_off_the_home_page_at_first_and_on_a_page_of_their_own(): void
    {
        SiteSettings::save(['home.layout' => null]);
        Banner::factory()->create(['title' => 'A slide']);
        Banner::factory()->poster()->create(['title' => 'A poster']);

        // The slides follow the welcome, one window-high screen each; the posters are not on the home page.
        $this->get('/')->assertOk()
            ->assertSeeInOrder(['<h1', 'aria-label="2 / 2"', 'alt="A slide"', 'aria-label="Next slide"'], false)
            ->assertDontSee('alt="A poster"', false);
        $this->assertSame(2, substr_count($this->get('/')->getContent(), 'data-slider-dot'));

        $this->get('/posters')->assertOk()->assertSeeInOrder(['alt="A slide"', 'alt="A poster"'], false);
        $this->get('/sitemap.xml')->assertSee(route('posters'));

        Banner::query()->delete();

        $this->get('/posters')->assertOk()->assertSee('Nothing has been added here yet.');
    }

    public function test_the_home_page_has_none_of_it_until_something_is_put_up(): void
    {
        $this->get('/')->assertOk()->assertDontSee('aria-label="Banners"', false)->assertDontSee('<h2 class="mt-2 text-2xl sm:text-3xl">Campaigns and notices', false)->assertDontSee('<dialog', false);
    }

    public function test_banners_and_posters_show_in_their_own_places_in_order(): void
    {
        Banner::factory()->create(['title' => 'Second banner', 'position' => 2]);
        Banner::factory()->create(['title' => 'First banner', 'position' => 1]);
        Banner::factory()->poster()->create(['title' => 'Dengue campaign poster']);

        $this->get('/')
            ->assertSeeInOrder(['aria-label="Banners"', 'alt="First banner"', 'alt="Second banner"', 'Campaigns and notices', 'alt="Dengue campaign poster"'], false)
            ->assertDontSee('<dialog', false);
    }

    public function test_the_slides_are_a_section_of_their_own_with_arrows_and_dots_only_when_there_is_more_than_one(): void
    {
        Banner::factory()->create(['title' => 'Only slide']);

        $page = $this->get('/')->getContent();

        // A section of its own, below the hospital's name and the announcements' place.
        $this->assertGreaterThan(strpos($page, '<section id="news"'), strpos($page, 'aria-label="Banners"'));
        $this->assertSame(1, substr_count($page, 'data-slider-next'));

        Banner::factory()->create(['title' => 'Second slide']);

        $page = $this->get('/')->getContent();

        // The welcome's arrows and dots (itself and two slides), and the section's own.
        $this->assertSame(2, substr_count($page, 'aria-label="Next slide"'));
        $this->assertSame(3 + 2, substr_count($page, 'data-slider-dot'));
        // Shown whole in a frame of one shape, whatever its own: nothing is cropped, and the page keeps its height.
        $this->assertSame(2, substr_count($page, 'aspect-[4/3] sm:aspect-[16/9] lg:aspect-[21/9]'));
        $this->assertSame(2, substr_count($page, 'class="relative size-full object-contain"'));
    }

    public function test_one_shows_only_while_it_is_switched_on_and_inside_its_dates(): void
    {
        Banner::factory()->create(['title' => 'Switched off', 'is_active' => false]);
        Banner::factory()->create(['title' => 'Not yet', 'starts_at' => now()->addDay()]);
        Banner::factory()->create(['title' => 'Over', 'starts_at' => now()->subWeek(), 'ends_at' => now()->subDay()]);
        Banner::factory()->create(['title' => 'On now', 'ends_at' => now()->addDay()]);

        $this->get('/')->assertSee('On now')->assertDontSee('Switched off')->assertDontSee('Not yet')->assertDontSee('Over');

        $this->travel(2)->days();
        $this->get('/')->assertSee('Not yet')->assertDontSee('On now');
    }

    public function test_no_more_show_at_once_than_anyone_would_reach(): void
    {
        Banner::factory()->count(10)->create();
        Banner::factory()->poster()->count(10)->create();

        $page = $this->get('/')->getContent();

        // Each is drawn twice: itself, and out of focus behind itself to fill its frame.
        $this->assertSame(8 + 8, substr_count($page, 'size-full object-contain'));
        $this->assertSame(8 + 8, substr_count($page, 'aria-hidden="true" loading="lazy" class="absolute'));
    }

    public function test_a_link_to_another_site_opens_in_a_new_tab_and_one_to_this_site_does_not(): void
    {
        config(['app.url' => 'http://localhost']);
        Banner::factory()->create(['title' => 'Elsewhere', 'url' => 'https://www.moph.go.th/campaign']);
        Banner::factory()->poster()->create(['title' => 'Here', 'url' => 'http://localhost/announcements']);

        $this->get('/')
            ->assertSee('<a href="https://www.moph.go.th/campaign" target="_blank" rel="noopener noreferrer"', false)
            ->assertSee('<a href="http://localhost/announcements" class="relative block', false);
    }

    public function test_a_poster_has_its_name_under_it_and_without_a_link_opens_its_picture(): void
    {
        $poster = Banner::factory()->poster()->create(['title' => 'Dengue campaign poster', 'url' => null]);

        $this->get('/')
            ->assertSee('<a href="'.$poster->imageUrl().'" target="_blank" rel="noopener noreferrer" class="relative block overflow-hidden bg-raised aspect-[3/4]">', false)
            ->assertSeeInOrder(['alt="Dengue campaign poster"', '<p class="line-clamp-2', 'Dengue campaign poster'], false);
    }

    public function test_the_first_poster_marked_for_it_opens_over_the_page(): void
    {
        Banner::factory()->poster()->popup()->create(['title' => 'Later in order', 'position' => 2]);
        $first = Banner::factory()->poster()->popup()->create(['title' => 'Clinic closed on Friday', 'position' => 1]);

        $page = $this->get('/')->getContent();

        $this->assertSame(1, substr_count($page, '<dialog'));
        $this->assertStringContainsString('data-popup="'.$first->id.'" aria-label="Clinic closed on Friday"', $page);
        // It can always be closed, by a button that says so.
        $this->assertMatchesRegularExpression('/<form method="dialog"[^>]*>\s*<button[^>]*>.*?Close<\/button>/s', $page);
        // Only the home page: nobody is stopped on their way to an announcement.
        $this->get('/announcements')->assertDontSee('<dialog', false);
    }

    public function test_what_staff_type_is_never_run_by_the_readers_browser(): void
    {
        Banner::factory()->create(['title' => '"><script>alert(1)</script>', 'url' => 'https://example.com/?a="><script>alert(2)</script>']);

        $page = $this->get('/')->getContent();

        $this->assertStringNotContainsString('<script>alert(1)', $page);
        $this->assertStringNotContainsString('<script>alert(2)', $page);
    }

    public function test_an_author_puts_up_a_poster_that_opens_over_the_page(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        Livewire::test(ManageBanners::class)
            ->callAction('create', [
                'image_path' => UploadedFile::fake()->image('poster.jpg', 1200, 1700),
                'title' => 'Free flu vaccine',
                'placement' => 'poster',
                'is_popup' => true,
            ])
            ->assertHasNoFormErrors();

        $poster = Banner::firstOrFail();

        $this->assertSame('Showing', $poster->state());
        $this->assertStringEndsWith('.webp', $poster->image_path);
        Storage::disk('public')->assertExists($poster->image_path);
        $this->assertTrue(Banner::popup()->is($poster));

        $poster->delete();
        Storage::disk('public')->assertMissing($poster->image_path);
    }

    public function test_a_picture_and_what_it_says_are_needed_and_the_link_must_be_a_web_address(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        Livewire::test(ManageBanners::class)
            ->callAction('create', ['title' => '', 'url' => 'javascript:alert(1)'])
            ->assertHasFormErrors(['image_path', 'title', 'url']);

        Livewire::test(ManageBanners::class)
            ->callAction('create', ['title' => 'A', 'image_path' => UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')])
            ->assertHasFormErrors(['image_path']);

        $this->assertSame(0, Banner::count());
    }

    public function test_the_screen_reads_in_thai(): void
    {
        app()->setLocale('th');

        $this->actingAs(User::factory()->create())->get('/admin/banners')->assertOk()->assertSee('สไลด์และโปสเตอร์');
    }
}
