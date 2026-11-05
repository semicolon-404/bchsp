<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\SitePopups\SitePopupResource;
use App\Models\Department;
use App\Models\SitePopup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Popups set up on their own, apart from the announcements: on their pages, within their days, as set. */
class SitePopupTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_popup_shows_on_its_pages_within_its_days_with_its_buttons_and_look(): void
    {
        SitePopup::create(['title' => 'Flu shots this week', 'body' => 'Free for over 65', 'button_label' => 'Register', 'button_url' => 'https://forms.gle/x', 'size' => 'large', 'placement' => 'corner', 'accent' => '#db2777', 'pages' => 'home', 'frequency' => 'daily']);

        $this->get('/')->assertSee('Flu shots this week')->assertSee('site-popup is-large is-corner', false)
            ->assertSee('data-frequency="daily"', false)->assertSee('href="https://forms.gle/x"', false)->assertSee('--accent: #db2777', false);
        // For the home page only.
        $this->get('/contact')->assertDontSee('Flu shots this week');

        SitePopup::create(['title' => 'Closed on Friday', 'pages' => 'all']);
        $this->get('/contact')->assertSee('Closed on Friday');
    }

    public function test_off_or_outside_its_days_it_is_not_shown_but_staff_can_preview_it(): void
    {
        $later = SitePopup::create(['title' => 'Next month', 'starts_at' => now()->addMonth()]);
        SitePopup::create(['title' => 'Switched off', 'is_active' => false]);
        SitePopup::create(['title' => 'Over', 'ends_at' => now()->subDay()]);

        $this->get('/')->assertDontSee('Next month')->assertDontSee('Switched off')->assertDontSee('Over');
        $this->get('/?popup='.$later->id)->assertDontSee('Next month');

        $this->actingAs(User::factory()->create(['role' => Role::Admin]))->get('/?popup='.$later->id)->assertSee('Next month');
    }

    public function test_only_the_administrators_set_them(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Author, 'department_id' => Department::factory()->create()->id]));
        $this->assertFalse(SitePopupResource::canAccess());
    }

    public function test_its_text_is_set_out_freely_and_shown_safely(): void
    {
        SitePopup::create(['title' => 'Notice', 'body' => '<h2>Free</h2><p style="text-align: center"><strong>Bold</strong> and <a href="https://x.test">a link</a></p><script>alert(1)</script>']);

        $this->get('/')->assertSee('<strong>Bold</strong>', false)->assertSee('href="https://x.test"', false)->assertDontSee('<script>alert(1)', false);
    }
}
