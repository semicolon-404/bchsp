<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteSettings as SettingsPage;
use App\Models\Announcement;
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
        Announcement::factory()->create(['title' => 'A slide', 'image_path' => 'announcements/pictures/slide.webp', 'in_slider' => true]);
        Announcement::factory()->create(['title' => 'A notice']);
        Department::factory()->create(['name' => 'A department']);
    }

    public function test_until_someone_arranges_it_the_page_is_in_its_own_order(): void
    {
        $this->fillTheHomePage();

        // The welcome, what is new, a list of the other parts of the site, and what the hospital says of itself.
        // A section for the slides alone and the departments are off at first.
        $this->assertSame(['hero', 'announcements', 'shortcuts', 'identity'], HomeLayout::shown());

        $this->get('/')
            ->assertSeeInOrder(['<h1', '<section id="news"', 'alt="A slide"', 'A notice', 'aria-label="Shortcuts"', 'id="door-0"', 'id="door-5"'], false)
            // Nothing has been written about the hospital yet, so that section takes no room.
            ->assertDontSee('id="identity"', false)
            ->assertDontSee('A department');

        // Switched on, each takes its usual place: after what people came to read.
        $this->showOnHome('departments');

        $this->get('/')->assertSeeInOrder(['<h1', '<section id="news"', 'aria-label="Shortcuts"', 'A department'], false);
    }

    public function test_the_welcome_carries_the_motto_and_what_the_hospital_says_of_itself_has_a_tab_each(): void
    {
        $this->get('/')->assertSee('Welcome to')->assertDontSee('id="identity"', false);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SettingsPage::class)
            ->fillForm(['site__motto' => 'Service from the heart', 'identity__vision' => 'A hospital of quality
Quality: a service that works', 'identity__values' => 'T - Teamwork

A - Agility'])
            ->call('save')
            ->assertHasNoFormErrors();

        auth()->logout();
        $page = $this->get('/')
            ->assertSeeInOrder(['Welcome to', '<h1', '“Service from the heart”', '<section id="identity"', '“A hospital of quality”', 'Quality: a service that works', 'T - Teamwork', 'A - Agility'], false)
            // Nothing was written for the mission: it has no tab.
            ->assertDontSee('for="identity-2"', false)
            ->getContent();

        $this->assertSame(2, substr_count($page, 'class="tab-panel'));
    }

    public function test_an_administrator_reorders_the_sections_and_hides_some(): void
    {
        $this->fillTheHomePage();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SettingsPage::class)
            ->assertFormSet(fn (array $state) => array_column(array_values($state['home_layout']), 'key') === array_keys(HomeLayout::SECTIONS))
            ->fillForm(['home_layout' => [
                ['key' => 'hero', 'shown' => true],
                ['key' => 'departments', 'shown' => true],
                ['key' => 'announcements', 'shown' => true],
                ['key' => 'slides', 'shown' => true],
                ['key' => 'shortcuts', 'shown' => false],
                ['key' => 'identity', 'shown' => false],
            ]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['hero', 'departments', 'announcements', 'slides'], HomeLayout::shown());

        auth()->logout();
        $this->get('/')
            ->assertSeeInOrder(['<h1', 'A department', '<section id="news"', 'aria-label="Banners"'], false)
            ->assertDontSee('aria-label="Shortcuts"', false);
    }

    public function test_the_shortcuts_lead_to_what_visitors_come_for_and_departments_can_be_switched_on(): void
    {
        $this->fillTheHomePage();
        $this->showOnHome('shortcuts');

        $this->get('/')
            ->assertSee('href="'.route('schedule').'"', false)
            ->assertSee('href="'.route('contact').'"', false)
            ->assertSee('href="'.route('announcements.index', ['type' => 'activities']).'"', false);

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

        // Saved before "identity" existed, say: it is added at the end, shown.
        SiteSettings::save(['home.layout' => [['key' => 'announcements', 'shown' => true], ['key' => 'hero', 'shown' => true]]]);

        $this->assertSame(['announcements', 'hero', 'shortcuts', 'identity'], HomeLayout::shown());
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
        $poster = Announcement::factory()->create(['title' => 'Clinic closed on Friday', 'image_path' => 'announcements/pictures/closed.webp', 'is_popup' => true]);
        // Marked for it, but with no picture to open: never that one.
        Announcement::factory()->create(['title' => 'Words only', 'is_popup' => true, 'is_pinned' => true]);
        SiteSettings::save(['home.layout' => [['key' => 'announcements', 'shown' => false], ['key' => 'slides', 'shown' => false]]]);

        $this->get('/')->assertSee('data-popup="'.$poster->id.'"', false)->assertSee('closed.webp', false);

        // It closes with the announcement.
        $poster->update(['ends_at' => now()->subMinute()]);
        $this->get('/')->assertDontSee('<dialog', false);
    }

    public function test_under_the_welcome_are_what_people_come_to_do_and_at_the_foot_how_to_call(): void
    {
        SiteSettings::save(['contact.phone' => '044-579076', 'contact.emergency_phone' => '1669', 'contact.address' => '221 Moo 4, Buachet, Surin']);

        $this->get('/')
            ->assertSeeInOrder([
                '<h1', 'aria-label="What would you like to do?"', 'href="'.route('schedule').'"', 'href="'.route('phones').'"', 'href="'.route('departments.index').'"',
                'aria-label="Shortcuts"', 'id="door-0"',
                'id="reach-heading"', 'href="tel:1669"', 'href="tel:044579076"', '221 Moo 4, Buachet, Surin',
            ], false)
            // A place with nothing in it yet is not offered.
            ->assertDontSee('href="'.route('faq').'"', false)
            ->assertDontSee('href="'.route('services').'"', false);

        // With no number to call there is no such band, and nothing to dial anywhere on the page.
        SiteSettings::save(['contact.phone' => null, 'contact.emergency_phone' => null]);
        $this->get('/')->assertDontSee('id="reach-heading"', false)->assertDontSee('tel:', false);
    }

    public function test_beside_the_news_are_its_kinds_and_what_is_coming_up_and_read_most(): void
    {
        Announcement::factory()->create(['title' => 'Blood drive', 'event_on' => now()->addDays(5)->toDateString(), 'views' => 40]);
        Announcement::factory()->create(['title' => 'Already happened', 'event_on' => now()->subDays(5)->toDateString()]);

        $page = $this->get('/')
            ->assertSeeInOrder(['<section id="news"', 'href="'.route('announcements.index', ['type' => 'jobs']).'"', 'Coming up', 'Blood drive', 'Most read', 'Blood drive'], false)
            ->getContent();

        $this->assertStringNotContainsString('Already happened', str($page)->between('Coming up', 'Most read')->toString());
    }

    public function test_the_section_reads_in_thai(): void
    {
        app()->setLocale('th');

        $this->actingAs(User::factory()->admin()->create())->get('/admin/site-settings')->assertSee('การจัดวางหน้าแรก');
    }

    public function test_the_ambulance_drives_with_its_lights_medium_until_the_administrator_turns_them_or_it_off(): void
    {
        $this->get('/')->assertSee('hero-siren is-medium', false)->assertSee('class="ambulance"', false);

        SiteSettings::save(['site.ambulance' => 'very']);
        $this->get('/')->assertSee('hero-siren is-very', false);

        SiteSettings::save(['site.ambulance' => 'off']);
        $this->get('/')->assertDontSee('class="ambulance"', false)->assertDontSee('hero-siren', false);
    }
}
