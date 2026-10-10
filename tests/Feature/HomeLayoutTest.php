<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteSettings as SettingsPage;
use App\Models\Announcement;
use App\Models\Banner;
use App\Models\BlogPost;
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
        Announcement::factory()->create(['title' => 'A notice']);
        Department::factory()->create(['name' => 'A department']);
        BlogPost::factory()->create(['title' => 'An article']);
    }

    public function test_until_someone_arranges_it_the_page_is_in_its_own_order(): void
    {
        $this->fillTheHomePage();

        // The list of departments is off at first: it is not what the home page is for.
        $this->assertSame(['slides', 'hero', 'shortcuts', 'announcements', 'posters', 'articles'], HomeLayout::shown());

        $this->get('/')
            ->assertSeeInOrder(['alt="A slide"', '<h1', 'aria-label="Shortcuts"', 'A notice', 'alt="A poster"', 'An article'], false)
            ->assertDontSee('A department');
    }

    public function test_an_administrator_reorders_the_sections_and_hides_some(): void
    {
        $this->fillTheHomePage();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SettingsPage::class)
            ->assertFormSet(fn (array $state) => array_column(array_values($state['home_layout']), 'key') === array_keys(HomeLayout::SECTIONS))
            ->fillForm(['home_layout' => [
                ['key' => 'hero', 'shown' => true],
                ['key' => 'articles', 'shown' => true],
                ['key' => 'announcements', 'shown' => true],
                ['key' => 'slides', 'shown' => true],
                ['key' => 'departments', 'shown' => false],
                ['key' => 'posters', 'shown' => false],
                ['key' => 'shortcuts', 'shown' => false],
            ]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['hero', 'articles', 'announcements', 'slides'], HomeLayout::shown());

        auth()->logout();
        $this->get('/')
            ->assertSeeInOrder(['<h1', 'An article', 'A notice', 'alt="A slide"'], false)
            ->assertDontSee('A department')
            ->assertDontSee('alt="A poster"', false);
    }

    public function test_the_shortcuts_lead_to_what_visitors_come_for_and_departments_can_be_switched_on(): void
    {
        $this->fillTheHomePage();

        $this->get('/')
            ->assertSee('href="'.route('schedule').'"', false)
            ->assertSee('href="'.route('contact').'"', false)
            ->assertSee('href="'.route('downloads').'"', false);

        SiteSettings::save(['home.layout' => [['key' => 'departments', 'shown' => true]]]);

        $this->get('/')->assertSee('A department')->assertSee('All departments');
    }

    public function test_the_heading_of_the_page_cannot_be_hidden(): void
    {
        SiteSettings::save(['home.layout' => [['key' => 'hero', 'shown' => false], ['key' => 'slides', 'shown' => false]]]);

        $this->assertContains('hero', HomeLayout::shown());
        $this->assertNotContains('slides', HomeLayout::shown());
        $this->get('/')->assertOk()->assertSee('<h1', false);
    }

    public function test_an_untouched_layout_is_not_saved_and_a_saved_one_still_gets_new_sections(): void
    {
        $this->assertNull(HomeLayout::toStore(HomeLayout::rows()));

        // Saved before "articles" existed, say: it is added at the end, shown.
        SiteSettings::save(['home.layout' => [['key' => 'announcements', 'shown' => true], ['key' => 'hero', 'shown' => true]]]);

        $this->assertSame(['announcements', 'hero', 'slides', 'shortcuts', 'posters', 'articles'], HomeLayout::shown());
    }

    public function test_whatever_is_saved_only_known_sections_are_drawn_each_once(): void
    {
        SiteSettings::save(['home.layout' => ['nonsense', ['key' => 'no-such', 'shown' => true], ['key' => 'hero'], ['key' => 'hero'], ['key' => '<script>']]]);

        $this->assertSame(1, count(array_keys(HomeLayout::shown(), 'hero')));
        $this->assertCount(count(HomeLayout::SECTIONS), HomeLayout::rows());
        $this->get('/')->assertOk();
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
