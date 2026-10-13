<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteSettings as SettingsPage;
use App\Models\User;
use App\Support\SiteSettings;
use App\Support\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class SiteColourTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_site_is_its_own_green_until_someone_changes_it(): void
    {
        $this->assertSame(Theme::GREEN, Theme::accent());
        $this->assertSame('', Theme::css());
        $this->get('/')->assertOk()->assertDontSee('--color-brand:', false)->assertDontSee('is-mourning', false);
    }

    public function test_one_colour_reaches_every_page_in_both_looks(): void
    {
        SiteSettings::save([Theme::MODE => 'fixed', Theme::COLOR => '#38BDF8']);

        $this->assertSame('#38bdf8', Theme::accent());
        $this->get('/')
            ->assertSee(':root{--color-brand:#38bdf8;', false)
            ->assertSee(":root[data-theme='light']{--color-brand:", false);
    }

    public function test_the_colour_of_the_day_changes_at_midnight_in_thailand(): void
    {
        SiteSettings::save([Theme::MODE => 'daily']);

        // A Monday in Thailand: yellow.
        Carbon::setTestNow(Carbon::parse('2026-10-12 23:59', 'Asia/Bangkok'));
        $this->assertSame('#facc15', Theme::accent());

        // A minute later it is Tuesday: pink.
        Carbon::setTestNow(Carbon::parse('2026-10-13 00:00', 'Asia/Bangkok'));
        $this->assertSame('#ff3d71', Theme::accent());
    }

    public function test_left_alone_the_site_keeps_its_colour_and_wears_the_day_as_a_touch(): void
    {
        // A Tuesday in Thailand: pink is the day's colour, and the site is still its own green.
        Carbon::setTestNow(Carbon::parse('2026-10-13 09:00', 'Asia/Bangkok'));

        $this->assertSame('touch', Theme::mode());
        $this->assertSame(Theme::GREEN, Theme::accent());
        $this->assertSame('#ff3d71', Theme::touchOfTheDay());

        $this->get('/')
            ->assertSee(':root{--color-day:#ff3d71}', false)
            ->assertSee('class="day-line"', false)
            ->assertSee('class="day-dot"', false)
            ->assertDontSee('--color-brand:#ff3d71', false);

        // One colour and nothing else; or the whole site in the day's colour: neither has the touches.
        foreach (['fixed', 'daily'] as $mode) {
            SiteSettings::save([Theme::MODE => $mode]);

            $this->assertNull(Theme::touchOfTheDay());
            $this->get('/')->assertDontSee('--color-day', false)->assertDontSee('class="day-line"', false);
        }

        $this->assertSame('#ff3d71', Theme::accent());
    }

    public function test_each_day_can_be_given_another_colour_and_a_bad_one_falls_back(): void
    {
        SiteSettings::save([Theme::MODE => 'daily', Theme::DAYS => ['#111111', 'not a colour', null]]);

        $this->assertSame('#111111', Theme::days()[0]);
        $this->assertSame(Theme::DAY_COLOURS[1], Theme::days()[1]);
        $this->assertSame(Theme::DAY_COLOURS[6], Theme::days()[6]);
    }

    public function test_whatever_colour_is_picked_it_can_be_read_in_both_looks(): void
    {
        foreach ([...array_keys(Theme::FAVOURITES), ...Theme::DAY_COLOURS, '#000000', '#ffffff', '#00008b', '#ffff00'] as $accent) {
            $palette = Theme::palette($accent);

            $this->assertGreaterThanOrEqual(4.5, Theme::contrast($palette['dark']['brand'], '#0d0d0d'), "$accent as text on the dark page");
            $this->assertGreaterThanOrEqual(4.5, Theme::contrast($palette['light']['brand'], '#ffffff'), "$accent as text on the light page");
            $this->assertGreaterThanOrEqual(4.5, Theme::contrast($palette['dark']['on'], $palette['dark']['brand']), "words on $accent, dark");
            $this->assertGreaterThanOrEqual(4.5, Theme::contrast($palette['light']['on'], $palette['light']['brand']), "words on $accent, light");
        }
    }

    public function test_something_that_is_not_a_colour_never_reaches_the_page(): void
    {
        SiteSettings::save([Theme::COLOR => 'red;}</style><script>alert(1)</script>']);

        $this->assertSame(Theme::GREEN, Theme::accent());
        $this->get('/')->assertDontSee('alert(1)', false);
    }

    public function test_mourning_turns_the_site_the_closed_page_and_the_panel_grey(): void
    {
        SiteSettings::save([Theme::MOURNING => true]);

        $this->get('/')->assertSee('class="is-mourning"', false);
        $this->get('/admin/login')->assertSee('html{filter:grayscale(1)}', false);

        SiteSettings::save(['maintenance.enabled' => true]);
        $this->get('/')->assertStatus(503)->assertSee('filter: grayscale(1)', false);
    }

    public function test_an_administrator_picks_a_colour_and_switches_mourning_on(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SettingsPage::class)
            ->assertFormSet(['theme__mode' => 'touch', 'theme__color' => Theme::GREEN, 'theme__mourning' => false, 'theme_day_1' => '#facc15'])
            ->fillForm(['theme__color' => '#ff7a1a', 'theme__mourning' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('#ff7a1a', Theme::accent());
        $this->assertTrue(Theme::isMourning());
    }

    public function test_an_administrator_sets_the_colours_of_the_days(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Carbon::setTestNow(Carbon::parse('2026-10-12 09:00', 'Asia/Bangkok'));

        Livewire::test(SettingsPage::class)
            ->fillForm(['theme__mode' => 'daily', 'theme_day_1' => '#123456'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(Theme::isDaily());
        $this->assertSame('#123456', Theme::accent());
        $this->assertSame(Theme::DAY_COLOURS[0], Theme::days()[0]);
    }

    public function test_a_colour_typed_wrongly_is_refused(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SettingsPage::class)
            ->fillForm(['theme__color' => 'green'])
            ->call('save')
            ->assertHasFormErrors(['theme__color']);
    }

    public function test_the_colour_section_reads_in_thai(): void
    {
        app()->setLocale('th');

        $this->actingAs(User::factory()->admin()->create())->get('/admin/site-settings')
            ->assertSee('สีของเว็บไซต์')
            ->assertSee('สีประจำวัน')
            ->assertSee('โหมดไว้อาลัย');
    }
}
