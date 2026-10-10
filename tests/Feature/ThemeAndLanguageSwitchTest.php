<?php

namespace Tests\Feature;

use App\Http\Middleware\SetLocale;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeAndLanguageSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_page_offers_the_light_the_dark_and_the_devices_own_look(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('data-theme-mode="light"', false)
            ->assertSee('data-theme-mode="dark"', false)
            ->assertSee('data-theme-mode="system"', false)
            ->assertSee('Dark theme');
    }

    public function test_the_site_opens_light_and_the_choice_is_on_the_page_before_the_stylesheet(): void
    {
        $page = $this->withVite()->get('/')->getContent();

        $this->assertStringContainsString("localStorage.getItem('theme') || 'light'", $page);
        // Light even where scripts do not run.
        $this->assertStringContainsString('data-theme="light"', $page);
        $this->assertStringContainsString("const DEFAULT = 'light';", file_get_contents(resource_path('js/theme.js')));
        $this->assertLessThan(strpos($page, 'rel="stylesheet"'), strpos($page, "localStorage.getItem('theme')"));

        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString(":root[data-theme='light']", $css);
        $this->assertStringNotContainsString(":root[data-theme='dark']", $css);
    }

    public function test_there_are_no_text_size_buttons(): void
    {
        $this->get('/')->assertDontSee('data-font-size', false);
    }

    public function test_the_site_is_in_thai_until_someone_chooses_english(): void
    {
        config(['app.locale' => 'th']);
        app()->setLocale('th');

        // Whatever the browser asks for: Thai is the site's language.
        $this->get('/', ['Accept-Language' => 'en-US,en;q=0.9'])
            ->assertSee('lang="th"', false)
            ->assertSee('ติดต่อเรา');
    }

    public function test_choosing_english_is_remembered_and_comes_back_to_the_same_page(): void
    {
        app()->setLocale('th');

        $this->from('/')->get('/language/en')
            ->assertRedirect('/')
            ->assertPlainCookie(SetLocale::COOKIE, 'en');

        $this->withUnencryptedCookie(SetLocale::COOKIE, 'en')->get('/')
            ->assertSee('lang="en"', false)
            ->assertSee('Contact us')
            ->assertDontSee('ติดต่อเรา');
    }

    public function test_choosing_thai_again_brings_thai_back(): void
    {
        app()->setLocale('th');

        $this->get('/language/th')->assertRedirect('/')->assertPlainCookie(SetLocale::COOKIE, 'th');
        $this->withUnencryptedCookie(SetLocale::COOKIE, 'th')->get('/')->assertSee('lang="th"', false);
    }

    public function test_a_language_the_site_does_not_have_is_not_found_and_a_made_up_cookie_is_ignored(): void
    {
        app()->setLocale('th');

        $this->get('/language/fr')->assertNotFound();
        $this->withUnencryptedCookie(SetLocale::COOKIE, '../../etc')->get('/')->assertOk()->assertSee('lang="th"', false);
    }

    public function test_the_switch_names_each_language_in_itself_and_marks_the_current_one(): void
    {
        app()->setLocale('th');

        $this->get('/')
            ->assertSee('ไทย')
            ->assertSee('English')
            ->assertSee('lang="th" rel="nofollow" aria-current="true"', false);
    }

    public function test_a_reader_in_english_sees_the_english_name_when_there_is_one(): void
    {
        SiteSettings::save(['app.name' => 'โรงพยาบาลบัวเชด']);

        $this->withUnencryptedCookie(SetLocale::COOKIE, 'en')->get('/')->assertSee('โรงพยาบาลบัวเชด');

        SiteSettings::save(['site.name_en' => 'Buachet Hospital']);

        $this->withUnencryptedCookie(SetLocale::COOKIE, 'en')->get('/')->assertSee('<title>Buachet Hospital</title>', false);
        $this->withUnencryptedCookie(SetLocale::COOKIE, 'th')->get('/')->assertSee('<title>โรงพยาบาลบัวเชด</title>', false);
    }

    public function test_the_year_is_the_buddhist_one_in_thai_and_the_common_one_in_english(): void
    {
        $this->withUnencryptedCookie(SetLocale::COOKIE, 'th')->get('/')->assertSee('© '.(now()->year + 543));
        $this->withUnencryptedCookie(SetLocale::COOKIE, 'en')->get('/')->assertSee('© '.now()->year.' ');
    }

    public function test_the_language_can_be_changed_while_the_site_is_closed(): void
    {
        SiteSettings::save(['maintenance.enabled' => true]);

        $this->get('/language/en')->assertRedirect();
        $this->withUnencryptedCookie(SetLocale::COOKIE, 'th')->get('/')->assertStatus(503)->assertSee('ปิดปรับปรุงชั่วคราว');
    }

    public function test_the_panel_stays_in_the_sites_own_language(): void
    {
        config(['app.locale' => 'th']);
        app()->setLocale('th');

        $this->withUnencryptedCookie(SetLocale::COOKIE, 'en')->get('/admin/login')->assertOk()->assertSee('ชื่อผู้ใช้หรืออีเมล');
    }
}
