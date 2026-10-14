<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Pages\SiteSettings as SiteSettingsPage;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The first screen of the home page: the welcome on the site's own drawing, then the hospital's photographs
 * one after another, each saying what is shown over it.
 */
class HeroPhotographsTest extends TestCase
{
    use RefreshDatabase;

    public function test_left_alone_the_first_screen_is_the_welcome_on_the_drawing_and_nothing_turns(): void
    {
        $this->assertSame([['art', 'welcome']], array_map(fn (array $slide) => [$slide['kind'], $slide['show']], SiteSettings::heroSlides()));

        $this->get('/')
            ->assertSee('data-kind="art" data-show="welcome"', false)
            ->assertSee('class="hero-art is-on"', false)
            ->assertDontSee('data-hero-dot', false)
            ->assertDontSee('hero-glass', false)
            ->assertDontSee('class="hero-photo', false)
            ->assertSee('"@context":"https://schema.org"', false);
    }

    public function test_photographs_follow_the_welcome_each_with_what_is_shown_over_it(): void
    {
        SiteSettings::save(['site.hero_slides' => [
            ['image' => 'site/one.webp', 'caption' => 'The new dental clinic', 'caption_en' => 'Dental, in English', 'url' => '/departments', 'show' => 'caption'],
            ['image' => 'site/two.webp', 'caption' => 'Blood donation day', 'url' => 'https://example.org/give', 'show' => 'welcome'],
            ['image' => 'site/three.webp', 'caption' => 'Never shown', 'url' => '', 'show' => 'none'],
            // Kept before there was a choice: the welcome is over it.
            ['image' => 'site/four.webp'],
        ]]);

        $slides = SiteSettings::heroSlides();
        $this->assertSame(['art', 'photo', 'photo', 'photo', 'photo'], array_column($slides, 'kind'));
        $this->assertSame(['welcome', 'caption', 'welcome', 'none', 'welcome'], array_column($slides, 'show'));

        $page = $this->get('/')->assertOk();
        $page->assertSee('data-hero', false)
            // It opens on the drawing; the photographs are fetched after the page.
            ->assertSee('class="home-screen" data-kind="art" data-show="welcome"', false)
            ->assertSee('data-src="'.e($slides[1]['url']).'"', false)
            ->assertSee('data-kind="photo" data-show="caption"', false)
            ->assertSee('data-kind="photo" data-show="none"', false)
            // The tests read in English: the first has its line in English, the second falls back to the Thai one.
            ->assertSeeInOrder(['Dental, in English', 'Blood donation day'])
            ->assertDontSee('Never shown')
            ->assertSee('href="/departments"', false)
            ->assertSee('href="https://example.org/give"', false)
            ->assertSee('Photograph 5 of 5')
            ->assertSee('hero-glass', false)
            ->assertSee('data-hero-pause', false);
        $this->assertSame(5, substr_count($page->getContent(), 'data-hero-dot'));

        // "About us" has the first photograph, still.
        $this->assertSame($slides[1]['url'], SiteSettings::heroUrl());
        $this->get('/about')->assertSee(e($slides[1]['url']), false)->assertDontSee('data-hero-dot', false);
    }

    public function test_the_welcome_on_the_drawing_can_be_switched_off_once_there_is_a_photograph(): void
    {
        SiteSettings::save(['site.hero_welcome' => false]);

        // With nothing else to show it stays: a first screen is never empty.
        $this->assertSame(['art'], array_column(SiteSettings::heroSlides(), 'kind'));

        SiteSettings::save(['site.hero_slides' => [['image' => 'site/one.webp', 'caption' => 'The front gate', 'show' => 'caption']]]);

        $this->assertSame(['photo'], array_column(SiteSettings::heroSlides(), 'kind'));
        $this->get('/')
            ->assertSee('class="home-screen" data-kind="photo" data-show="caption"', false)
            ->assertSee('class="hero-photo is-on"', false)
            ->assertSee('The front gate')
            ->assertDontSee('class="hero-art', false)
            ->assertDontSee('data-hero-dot', false);
    }

    public function test_only_a_page_of_this_site_or_a_web_address_is_kept_as_a_link_and_no_more_than_eight(): void
    {
        $rows = SiteSettings::heroRows([
            ['image' => 'site/a.webp', 'url' => 'javascript:alert(1)', 'show' => 'everything'],
            ['image' => 'site/b.webp', 'url' => '//evil.example'],
            ['image' => ['uuid' => 'site/c.webp'], 'url' => '/schedule', 'show' => 'none'],
            ['image' => '', 'caption' => 'No picture'],
            'nonsense',
            ...array_fill(0, 10, ['image' => 'site/more.webp']),
        ]);

        $this->assertCount(SiteSettings::MAX_HERO_SLIDES, $rows);
        $this->assertSame(['', '', '/schedule'], array_column(array_slice($rows, 0, 3), 'url'));
        $this->assertSame(['welcome', 'welcome', 'none'], array_column(array_slice($rows, 0, 3), 'show'));
        // A file field hands its one file over as a list.
        $this->assertSame('site/c.webp', $rows[2]['image']);
    }

    public function test_an_administrator_arranges_the_first_screen(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));

        Livewire::test(SiteSettingsPage::class)
            ->assertFormSet(['site__hero_welcome' => true, 'hero_slides' => []])
            ->fillForm([
                'site__hero_welcome' => false,
                'hero_slides' => [
                    ['image' => ['site/new.webp'], 'show' => 'caption', 'caption' => 'The front gate', 'caption_en' => '', 'url' => '/about'],
                    ['image' => ['site/old.webp'], 'show' => 'none', 'caption' => '', 'caption_en' => '', 'url' => ''],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse(SiteSettings::get('site.hero_welcome'));
        $this->assertSame(['site/new.webp', 'site/old.webp'], array_column(SiteSettings::heroRows(), 'image'));
        $this->assertSame(['caption', 'none'], array_column(SiteSettings::heroRows(), 'show'));
        $this->assertSame('site/new.webp', SiteSettings::get('site.hero_image'));

        // All taken away: the drawing with the welcome is the first screen again.
        Livewire::test(SiteSettingsPage::class)->fillForm(['hero_slides' => []])->call('save')->assertHasNoFormErrors();

        $this->assertNull(SiteSettings::heroUrl());
        $this->get('/')->assertSee('class="hero-art is-on"', false);
    }
}
