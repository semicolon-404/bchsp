<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteSettings as SettingsPage;
use App\Models\Announcement;
use App\Models\Banner;
use App\Models\BlogPost;
use App\Models\ClinicSession;
use App\Models\Department;
use App\Models\User;
use App\Support\HomeLayout;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HomeLayoutTest extends TestCase
{
    use RefreshDatabase;

    private function fillTheHomePage(): void
    {
        Banner::factory()->create(['title' => 'A slide']);
        Banner::factory()->poster()->create(['title' => 'A poster']);
        ClinicSession::factory()->create(['name' => 'A clinic', 'days' => [0, 1, 2, 3, 4, 5, 6]]);
        Announcement::factory()->create(['title' => 'A notice']);
        Department::factory()->create(['name' => 'A department']);
        BlogPost::factory()->create(['title' => 'An article']);
    }

    public function test_until_someone_arranges_it_the_page_is_in_its_own_order(): void
    {
        $this->fillTheHomePage();

        // The list of departments is off at first: it is not what the home page is for.
        $this->assertSame(['slides', 'today', 'announcements', 'posters', 'articles'], HomeLayout::shown());

        $this->get('/')
            ->assertSeeInOrder(['<h1', 'alt="A slide"', 'A clinic', 'A notice', 'alt="A poster"', 'An article'], false)
            ->assertDontSee('A department');
    }

    public function test_an_administrator_reorders_the_sections_and_hides_some(): void
    {
        $this->fillTheHomePage();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SettingsPage::class)
            ->assertFormSet(fn (array $state) => array_column(array_values($state['home_layout']), 'key') === array_keys(HomeLayout::SECTIONS))
            ->fillForm(['home_layout' => [
                ['key' => 'slides', 'shown' => true],
                ['key' => 'articles', 'shown' => true],
                ['key' => 'announcements', 'shown' => true],
                ['key' => 'departments', 'shown' => true],
                ['key' => 'today', 'shown' => false],
                ['key' => 'posters', 'shown' => false],
            ]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['slides', 'articles', 'announcements', 'departments'], HomeLayout::shown());

        auth()->logout();
        $this->get('/')
            ->assertSeeInOrder(['<h1', 'An article', 'A notice', 'A department'], false)
            ->assertDontSee('A clinic')
            ->assertDontSee('alt="A poster"', false);
    }

    public function test_the_slides_cannot_be_hidden_for_they_hold_the_heading_of_the_page(): void
    {
        SiteSettings::save(['home.layout' => [['key' => 'slides', 'shown' => false], ['key' => 'today', 'shown' => false]]]);

        $this->assertContains('slides', HomeLayout::shown());
        $this->assertNotContains('today', HomeLayout::shown());
        $this->get('/')->assertOk()->assertSee('<h1', false);
    }

    public function test_an_untouched_layout_is_not_saved_and_a_saved_one_still_gets_new_sections(): void
    {
        $this->assertNull(HomeLayout::toStore(HomeLayout::rows()));

        // Saved before "articles" existed, say: it is added at the end, shown.
        SiteSettings::save(['home.layout' => [['key' => 'announcements', 'shown' => true], ['key' => 'slides', 'shown' => true]]]);

        $this->assertSame(['announcements', 'slides', 'today', 'posters', 'articles'], HomeLayout::shown());
    }

    public function test_a_layout_saved_with_sections_that_no_longer_exist_still_draws_the_page(): void
    {
        // "hero" and "shortcuts" were sections once; their content is now the first slide.
        SiteSettings::save(['home.layout' => ['nonsense', ['key' => 'hero', 'shown' => true], ['key' => 'shortcuts', 'shown' => false], ['key' => 'slides'], ['key' => 'slides'], ['key' => '<script>']]]);

        $this->assertSame(1, count(array_keys(HomeLayout::shown(), 'slides')));
        $this->assertCount(count(HomeLayout::SECTIONS), HomeLayout::rows());
        $this->get('/')->assertOk()->assertSee('<h1', false);
    }

    public function test_the_poster_that_opens_over_the_page_does_not_depend_on_the_layout(): void
    {
        Banner::factory()->poster()->popup()->create(['title' => 'Clinic closed on Friday']);
        SiteSettings::save(['home.layout' => [['key' => 'posters', 'shown' => false]]]);

        $this->get('/')->assertSee('<dialog', false);
    }

    public function test_the_section_reads_in_thai(): void
    {
        app()->setLocale('th');

        $this->actingAs(User::factory()->admin()->create())->get('/admin/site-settings')->assertSee('การจัดวางหน้าแรก');
    }
}
