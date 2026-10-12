<?php

namespace Tests\Feature;

use App\Models\ClinicSession;
use App\Models\Department;
use App\Models\SitePage;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** /about: the hospital's profile, made of what staff keep in the panel and of nothing else. */
class HospitalProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_profile_presents_the_hospital_from_what_the_panel_holds(): void
    {
        SiteSettings::save([
            'app.name' => 'Buachet Hospital', 'site.motto' => 'Service from the heart',
            'contact.address' => '221 Moo 4, Buachet, Surin', 'contact.phone' => '044-579076', 'contact.emergency_phone' => '1669',
            'contact.hours' => "Emergency: all day, every day\nOutpatients: 08.00–16.00",
            'identity.vision' => "A hospital of quality\nQuality: a service that works",
            'identity.mission' => "Good care\nSafe care",
            'identity.values' => "T — Teamwork\nE — Ethic",
        ]);
        $history = SitePage::factory()->create(['title' => 'History of the hospital', 'slug' => 'history', 'section' => 'about', 'position' => 1, 'body' => '<h2>History of the hospital</h2><p>Opened in 1984 as a ten-bed hospital.&nbsp;</p>']);
        $dental = Department::factory()->create(['name' => 'Dental', 'location' => 'Building 2']);
        ClinicSession::factory()->create(['name' => 'Emergency room', 'days' => [0, 1, 2, 3, 4, 5, 6], 'starts_at' => '00:00', 'ends_at' => '23:59']);

        $this->get('/about')
            ->assertOk()
            ->assertSeeInOrder([
                '<h1', 'Buachet Hospital', '“Service from the heart”', '221 Moo 4, Buachet, Surin', 'href="tel:044579076"',
                // The row of links to every part that is on the page.
                'href="#identity"', 'href="#story"', 'href="#hours"', 'href="#departments"', 'href="#more"', 'href="#reach"',
                'id="identity"', '“A hospital of quality”', 'Quality', 'a service that works', 'Good care', 'Safe care', 'Teamwork', 'Ethic',
                'id="story"', 'Opened in 1984 as a ten-bed hospital.', 'href="'.route('pages.show', $history).'"',
                'id="hours"', 'Emergency', 'all day, every day', 'Emergency room',
                'id="departments"', route('departments.show', $dental), 'Dental', 'Building 2',
                'id="more"', 'History of the hospital', 'Clinic schedule', 'Contact us',
                'id="reach"', 'href="tel:1669"',
            ], false);
    }

    public function test_the_story_is_not_begun_with_its_own_title_and_the_values_spell_their_word(): void
    {
        SiteSettings::save(['identity.values' => "T — Teamwork\nE — Ethic"]);
        SitePage::factory()->create(['title' => 'History of the hospital', 'section' => 'about', 'body' => '<p>History of the hospital</p><p>Opened in 1984.</p>']);

        $page = $this->get('/about')->assertOk()->getContent();

        $this->assertStringContainsString('<p class="border-l-4 border-brand pl-5 text-lg leading-relaxed">Opened in 1984.</p>', $page);
        // The letters as tiles, then each with what it stands for.
        $this->assertSame(2, substr_count($page, 'rounded-lg bg-brand font-mono text-xl font-bold text-on-brand'));

        // Values that are not letters are a plain list, with no tiles.
        SiteSettings::save(['identity.values' => "Kindness\nHonesty"]);
        $this->get('/about')->assertSee('Kindness')->assertDontSee('rounded-lg bg-brand font-mono text-xl font-bold text-on-brand', false);
    }

    public function test_a_part_with_nothing_in_it_takes_no_room_and_has_no_link(): void
    {
        $this->get('/about')
            ->assertOk()
            ->assertDontSee('id="identity"', false)->assertDontSee('href="#identity"', false)
            ->assertDontSee('id="story"', false)->assertDontSee('href="#story"', false)
            ->assertDontSee('id="hours"', false)
            ->assertDontSee('id="departments"', false)
            // What is always there: the pages and places filed under About us, and how to reach the hospital.
            ->assertSee('id="more"', false)
            ->assertSee('id="reach"', false);
    }

    public function test_nothing_typed_in_the_panel_is_run_by_the_readers_browser_and_the_page_reads_in_thai(): void
    {
        SiteSettings::save(['site.motto' => '<script>alert(1)</script>', 'identity.vision' => '<img src=x onerror=alert(2)>', 'contact.hours' => 'Emergency: <b>always</b>']);

        $page = $this->get('/about')->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert(1)', $page);
        $this->assertStringNotContainsString('<img src=x', $page);
        $this->assertStringNotContainsString('<b>always</b>', $page);

        app()->setLocale('th');
        $this->get('/about')->assertSee('สิ่งที่เรายึดมั่น')->assertSee('รู้จักเราให้มากขึ้น')->assertSee('เราอยู่ตรงนี้เมื่อคุณต้องการ');
    }
}
