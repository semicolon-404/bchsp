<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteSettings as SettingsPage;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContactPageTest extends TestCase
{
    use RefreshDatabase;

    private const MAP = 'https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d15449!2d103.94!3d14.52';

    public function test_the_page_shows_how_to_reach_the_hospital(): void
    {
        SiteSettings::save([
            'contact.address' => '221 Moo 4, Buachet, Surin 32230',
            'contact.phone' => '044-579076',
            'contact.emergency_phone' => '1669',
            'contact.email' => 'info@example.com',
            'contact.hours' => "Emergency: 24 hours\nGeneral clinic: 08.00–16.00 every day",
        ]);

        $this->get('/contact')
            ->assertOk()
            ->assertSee('<title>Contact us · ', false)
            ->assertSee('221 Moo 4, Buachet, Surin 32230')
            ->assertSee('href="tel:044579076"', false)
            ->assertSee('href="tel:1669"', false)
            ->assertSee('mailto:info@example.com', false)
            ->assertSee('General clinic: 08.00–16.00 every day')
            ->assertSee('Emergency, 24 hours a day');
    }

    public function test_what_has_not_been_filled_in_is_left_out(): void
    {
        $this->get('/contact')
            ->assertOk()
            ->assertDontSee('tel:', false)
            ->assertDontSee('Show the map')
            ->assertDontSee('Complaints and suggestions')
            ->assertDontSee('<iframe', false);
    }

    public function test_the_map_is_fetched_from_google_only_when_the_visitor_asks(): void
    {
        SiteSettings::save(['contact.map' => self::MAP]);

        $page = $this->get('/contact')->assertOk()->assertSee('Show the map')->getContent();

        // The address waits in a data attribute; no frame, picture or script of Google's is in the page as it arrives.
        $this->assertStringContainsString('data-map="'.e(self::MAP).'"', $page);
        $this->assertStringNotContainsString('<iframe', $page);
        $this->assertStringNotContainsString('src="https://www.google.com', $page);
    }

    public function test_only_a_google_maps_embed_can_be_framed(): void
    {
        foreach (['https://evil.example/maps/embed?x', 'javascript:alert(1)', 'https://www.google.com/maps/embed?x"><script>', 'http://www.google.com/maps/embed?x'] as $url) {
            SiteSettings::save(['contact.map' => $url]);

            $this->assertNull(SiteSettings::mapEmbedUrl(), $url);
            $this->get('/contact')->assertOk()->assertDontSee('Show the map');
        }
    }

    public function test_the_hospitals_own_forms_are_linked_and_the_site_has_no_form_of_its_own(): void
    {
        SiteSettings::save([
            'feedback.complaint' => 'https://docs.google.com/forms/d/e/abc/viewform',
            'feedback.survey' => 'https://docs.google.com/forms/d/e/xyz/viewform',
            'feedback.suggestion' => 'javascript:alert(1)',
        ]);

        $this->get('/contact')
            ->assertOk()
            ->assertSee('Make a complaint')
            ->assertSee('https://docs.google.com/forms/d/e/abc/viewform', false)
            ->assertSee('Satisfaction survey')
            ->assertDontSee('Make a suggestion')
            ->assertDontSee('javascript:alert', false)
            ->assertSee('rel="noopener noreferrer"', false)
            ->assertSee('does not collect or keep personal data')
            // Nothing a visitor types is sent to this site.
            ->assertDontSee('<form', false)
            ->assertDontSee('<input', false);
    }

    public function test_an_administrator_sets_the_map_and_the_forms(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SettingsPage::class)
            ->fillForm(['contact__map' => 'https://maps.app.goo.gl/short', 'feedback__complaint' => 'http://insecure.example/form'])
            ->call('save')
            ->assertHasFormErrors(['contact__map', 'feedback__complaint']);

        Livewire::test(SettingsPage::class)
            ->fillForm(['contact__map' => self::MAP, 'contact__hours' => 'Every day', 'feedback__complaint' => 'https://forms.gle/abc'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(self::MAP, SiteSettings::mapEmbedUrl());
        $this->assertSame(['Make a complaint' => 'https://forms.gle/abc'], SiteSettings::feedbackLinks());
    }

    public function test_the_page_is_in_the_menus_and_reads_in_thai(): void
    {
        app()->setLocale('th');
        SiteSettings::save(['contact.map' => self::MAP, 'contact.emergency_phone' => '1669']);

        $this->get('/contact')
            ->assertSee('ติดต่อเรา')
            ->assertSee('แสดงแผนที่')
            ->assertSee('ฉุกเฉิน ตลอด 24 ชั่วโมง')
            // It belongs to "About us", which is the group marked in the bar.
            ->assertSee('href="'.route('about').'" aria-current="page"', false);
    }
}
