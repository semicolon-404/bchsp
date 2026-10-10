<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteSettings as SettingsPage;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_site_is_open_until_it_is_closed(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_a_visitor_sees_the_closed_page_and_search_engines_are_told_to_come_back(): void
    {
        SiteSettings::save(['maintenance.enabled' => true]);

        $this->get('/')
            ->assertStatus(503)
            ->assertHeader('Retry-After', 3600)
            ->assertSee('Back soon')
            ->assertSee('We are working on the website right now.');
    }

    public function test_the_closed_page_says_what_the_administrator_wrote_and_keeps_the_phone_numbers(): void
    {
        SiteSettings::save([
            'maintenance.enabled' => true,
            'maintenance.message' => 'Open again at 3 pm.',
            'contact.phone' => '044 579 076',
            'contact.emergency_phone' => '1669',
        ]);

        $this->get('/')
            ->assertStatus(503)
            ->assertSee('Open again at 3 pm.')
            ->assertSee('044 579 076')
            ->assertSee('tel:044579076', false)
            ->assertSee('tel:1669', false);
    }

    public function test_an_administrator_carries_on_and_an_author_does_not(): void
    {
        SiteSettings::save(['maintenance.enabled' => true]);

        $this->actingAs(User::factory()->admin()->create())->get('/')->assertOk();
        $this->actingAs(User::factory()->create())->get('/')->assertStatus(503);
    }

    public function test_the_way_in_stays_open(): void
    {
        SiteSettings::save(['maintenance.enabled' => true]);

        $this->get('/admin/login')->assertOk();
        $this->get('/up')->assertOk();
        $this->actingAs(User::factory()->create())->get('/admin')->assertOk();
    }

    public function test_an_administrator_closes_and_opens_the_site_from_the_settings(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SettingsPage::class)->fillForm(['maintenance__enabled' => true])->call('save')->assertHasNoFormErrors();
        $this->assertTrue((bool) SiteSettings::get('maintenance.enabled'));

        Livewire::test(SettingsPage::class)->fillForm(['maintenance__enabled' => false])->call('save')->assertHasNoFormErrors();
        $this->assertFalse((bool) SiteSettings::get('maintenance.enabled'));

        auth()->logout();
        $this->get('/')->assertOk();
    }
}
