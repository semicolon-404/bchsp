<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteSettings as SettingsPage;
use App\Models\User;
use App\Support\SiteSettings;
use App\Support\TwoStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_an_administrator_reaches_the_settings(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/site-settings')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/admin/site-settings')->assertOk();
    }

    public function test_a_setting_nobody_changed_keeps_its_config_value_and_has_no_row(): void
    {
        config(['app.name' => 'From the config']);

        $this->assertSame('From the config', SiteSettings::get('app.name'));
        $this->assertNull(SiteSettings::get('contact.phone'));
        $this->assertSame('none', SiteSettings::get('contact.phone', 'none'));
        $this->assertSame(0, DB::table('settings')->count());
    }

    public function test_an_administrator_saves_the_hospital_details(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SettingsPage::class)
            ->fillForm([
                'app__name' => 'Buachet Hospital',
                'contact__address' => '1 Moo 1, Buachet, Surin',
                'contact__phone' => '044 579 076',
                'contact__emergency_phone' => '1669',
                'social__facebook' => 'https://www.facebook.com/buachet',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertSame('Buachet Hospital', config('app.name'));
        $this->assertSame('1669', SiteSettings::get('contact.emergency_phone'));
        $this->assertSame(['Facebook' => 'https://www.facebook.com/buachet'], SiteSettings::socialLinks());
    }

    public function test_what_was_saved_is_there_when_the_site_next_starts(): void
    {
        SiteSettings::save(['app.name' => 'Buachet Hospital', 'bchsp.security.staff_two_step' => true]);

        config(['app.name' => 'Laravel', 'bchsp.security.staff_two_step' => false]);
        SiteSettings::apply();

        $this->assertSame('Buachet Hospital', config('app.name'));
        $this->assertTrue(TwoStep::isRequiredOfStaff());
    }

    public function test_the_page_opens_with_what_is_in_force(): void
    {
        SiteSettings::save(['contact.phone' => '044 579 076']);
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SettingsPage::class)
            ->assertFormSet(['contact__phone' => '044 579 076', 'app__name' => config('app.name'), 'bchsp__security__staff_two_step' => false]);
    }

    public function test_the_name_is_needed_and_a_link_must_be_a_web_address(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SettingsPage::class)
            ->fillForm(['app__name' => '', 'social__facebook' => 'not a link', 'contact__email' => 'not an email'])
            ->call('save')
            ->assertHasFormErrors(['app__name', 'social__facebook', 'contact__email']);

        $this->assertSame(0, DB::table('settings')->count());
    }

    public function test_a_logo_is_uploaded_and_has_an_address(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());

        $this->assertNull(SiteSettings::logoUrl());

        Livewire::test(SettingsPage::class)
            ->fillForm(['site__logo' => UploadedFile::fake()->image('logo.png', 300, 300)])
            ->call('save')
            ->assertHasNoFormErrors();

        $path = SiteSettings::get('site.logo');

        $this->assertIsString($path);
        Storage::disk('public')->assertExists($path);
        $this->assertStringContainsString($path, SiteSettings::logoUrl());
    }

    public function test_the_logo_is_left_out_of_the_top_bar_when_switched_off(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SettingsPage::class)
            ->assertFormSet(['site__show_logo' => true])
            ->fillForm(['site__logo' => UploadedFile::fake()->image('logo.png', 300, 300)])
            ->call('save')
            ->assertHasNoFormErrors();

        $bar = fn (): string => str($this->get('/about')->assertOk()->getContent())->between('<header', '</header>')->toString();

        $this->assertStringContainsString(SiteSettings::logoUrl(), $bar());

        SiteSettings::save(['site.show_logo' => false]);

        // The picture is kept, and the home page still has it.
        $this->assertNotNull(SiteSettings::logoUrl());
        $this->assertStringNotContainsString('<img', $bar());
        $this->assertStringNotContainsString('bg-brand text-on-brand', $bar());
    }

    public function test_a_logo_that_could_carry_a_script_is_refused(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SettingsPage::class)
            ->fillForm(['site__logo' => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')])
            ->call('save')
            ->assertHasFormErrors(['site__logo']);
    }

    public function test_keys_that_are_not_settings_are_not_kept(): void
    {
        SiteSettings::save(['app.key' => 'stolen', 'database.default' => 'elsewhere', 'contact.phone' => '1']);

        $this->assertSame(['contact.phone'], DB::table('settings')->pluck('key')->all());
    }

    public function test_turning_on_two_step_in_the_settings_sends_staff_to_set_it_up(): void
    {
        SiteSettings::save(['bchsp.security.staff_two_step' => true]);

        $this->actingAs(User::factory()->create())->get('/admin')
            ->assertRedirect(route('filament.admin.auth.multi-factor-authentication.set-up-required'));
    }

    public function test_the_page_reads_in_thai(): void
    {
        app()->setLocale('th');

        $this->actingAs(User::factory()->admin()->create())->get('/admin/site-settings')
            ->assertOk()
            ->assertSee('ตั้งค่าเว็บไซต์')
            ->assertSee('เบอร์ฉุกเฉิน')
            ->assertSee('บันทึกการตั้งค่า');
    }
}
