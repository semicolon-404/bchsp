<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Pages\SiteSettings as SiteSettingsPage;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** The photographs behind the first screen of the home page: several, each with its line, one after another. */
class HeroPhotographsTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_photograph_is_shown_clear_with_the_welcome_on_a_pane_and_nothing_to_turn(): void
    {
        // A site set up before there could be several: its one photograph is the only one.
        SiteSettings::save(['site.hero_image' => 'site/hospital.webp']);

        $this->assertCount(1, SiteSettings::heroSlides());
        $this->get('/')
            ->assertSee('class="hero-photo is-on"', false)
            ->assertSee('hero-glass', false)
            ->assertSee('has-photo', false)
            ->assertDontSee('data-hero-dot', false)
            ->assertDontSee('data-hero ', false)
            ->assertDontSee('class="hero-art"', false);
    }

    public function test_several_photographs_turn_each_with_its_line_and_where_it_leads(): void
    {
        SiteSettings::save(['site.hero_slides' => [
            ['image' => 'site/one.webp', 'caption' => 'The new dental clinic', 'caption_en' => 'Dental, in English', 'url' => '/departments'],
            ['image' => 'site/two.webp', 'caption' => 'Blood donation day', 'url' => 'https://example.org/give'],
            ['image' => 'site/three.webp', 'caption' => '', 'url' => ''],
        ]]);

        $page = $this->get('/')->assertOk();
        $page->assertSee('data-hero', false)
            // The first comes with the page; the others are fetched after it.
            ->assertSee('src="'.e(SiteSettings::heroSlides()[0]['url']).'"', false)
            ->assertSee('data-src="'.e(SiteSettings::heroSlides()[1]['url']).'"', false)
            // The tests read in English: the first has its line in English, the second falls back to the Thai one.
            ->assertSeeInOrder(['Dental, in English', 'Blood donation day'])
            ->assertSee('"@context":"https://schema.org"', false)
            ->assertSee('href="/departments"', false)
            ->assertSee('href="https://example.org/give"', false)
            ->assertSee('Photograph 3 of 3')
            ->assertSee('data-hero-pause', false);
        $this->assertSame(3, substr_count($page->getContent(), 'data-hero-dot'));
        $this->assertSame(3, substr_count($page->getContent(), 'data-hero-slide'));

        // "About us" keeps to the first, still.
        $this->get('/about')->assertSee(e(SiteSettings::heroSlides()[0]['url']), false)->assertDontSee('data-hero-dot', false);

        app()->setLocale('en');
        $this->assertSame('Dental, in English', SiteSettings::heroSlides()[0]['caption']);
        $this->assertSame('Blood donation day', SiteSettings::heroSlides()[1]['caption']);
    }

    public function test_only_a_page_of_this_site_or_a_web_address_is_kept_as_a_link_and_no_more_than_eight(): void
    {
        $rows = SiteSettings::heroRows([
            ['image' => 'site/a.webp', 'url' => 'javascript:alert(1)'],
            ['image' => 'site/b.webp', 'url' => '//evil.example'],
            ['image' => ['uuid' => 'site/c.webp'], 'url' => '/schedule'],
            ['image' => '', 'caption' => 'No picture'],
            'nonsense',
            ...array_fill(0, 10, ['image' => 'site/more.webp']),
        ]);

        $this->assertCount(SiteSettings::MAX_HERO_SLIDES, $rows);
        $this->assertSame(['', '', '/schedule'], array_column(array_slice($rows, 0, 3), 'url'));
        // A file field hands its one file over as a list.
        $this->assertSame('site/c.webp', $rows[2]['image']);
    }

    public function test_an_administrator_arranges_the_photographs_and_the_first_is_the_picture_of_the_hospital(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
        SiteSettings::save(['site.hero_image' => 'site/old.webp']);

        // What was there before shows as the first row.
        Livewire::test(SiteSettingsPage::class)
            ->assertFormSet(fn (array $state) => array_column(array_values($state['hero_slides']), 'caption') === ['']);

        Livewire::test(SiteSettingsPage::class)
            ->fillForm(['hero_slides' => [
                ['image' => ['site/new.webp'], 'caption' => 'The front gate', 'caption_en' => '', 'url' => '/about'],
                ['image' => ['site/old.webp'], 'caption' => '', 'caption_en' => '', 'url' => ''],
            ]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['site/new.webp', 'site/old.webp'], array_column(SiteSettings::heroRows(), 'image'));
        $this->assertSame('site/new.webp', SiteSettings::get('site.hero_image'));
        $this->assertSame('The front gate', SiteSettings::heroSlides()[0]['caption']);

        // All taken away: the drawing stands in again.
        Livewire::test(SiteSettingsPage::class)->fillForm(['hero_slides' => []])->call('save')->assertHasNoFormErrors();

        $this->assertNull(SiteSettings::heroUrl());
        $this->get('/')->assertSee('class="hero-art"', false);
    }
}
