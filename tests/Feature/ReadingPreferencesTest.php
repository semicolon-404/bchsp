<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_page_offers_larger_and_smaller_words_and_the_dark_look(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('data-font-size="smaller"', false)
            ->assertSee('data-font-size="normal"', false)
            ->assertSee('data-font-size="larger"', false)
            ->assertSee('data-theme-toggle', false)
            ->assertSee('aria-label="Larger text"', false)
            ->assertSee('Dark mode');
    }

    public function test_the_choice_is_put_on_the_page_before_the_stylesheet_is_read(): void
    {
        $page = $this->withVite()->get('/')->getContent();

        $this->assertLessThan(strpos($page, 'rel="stylesheet"'), strpos($page, "localStorage.getItem('theme')"));
    }

    public function test_the_first_paint_and_the_buttons_agree_on_keys_and_sizes(): void
    {
        $head = file_get_contents(resource_path('views/components/site/prefs.blade.php'));
        $script = file_get_contents(resource_path('js/prefs.js'));

        foreach (["'theme'", "'font-size'", '[100, 112.5, 125, 140]'] as $shared) {
            $this->assertStringContainsString($shared, $head);
            $this->assertStringContainsString($shared, $script);
        }
    }

    public function test_the_site_opens_light_and_the_words_start_at_eighteen_pixels(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('font-size: 112.5%', $css);
        $this->assertStringContainsString(":root[data-theme='dark']", $css);
        $this->assertStringNotContainsString(":root[data-theme='light']", $css);
    }

    public function test_the_controls_read_in_thai(): void
    {
        app()->setLocale('th');

        $this->get('/')->assertSee('ขนาดตัวอักษร')->assertSee('โหมดมืด')->assertSee('เพิ่มขนาดตัวอักษร');
    }
}
