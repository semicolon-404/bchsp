<?php

namespace Tests\Feature;

use App\Filament\Resources\SiteUpdates\Pages\ManageSiteUpdates;
use App\Models\SiteUpdate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** /updates: what has changed on the website, release by release, and the newest one named in every footer. */
class SiteUpdatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_site_starts_with_the_releases_made_so_far(): void
    {
        $this->assertSame(['0.3', '0.2', '0.1'], SiteUpdate::released()->pluck('version')->all());

        $this->get('/updates')
            ->assertOk()
            ->assertSeeInOrder(['What is new on this website', 'Newest release', '0.3', 'Releases so far', 'v0.3', 'v0.2', 'v0.1'])
            ->assertDontSee('Being worked on');
    }

    public function test_a_release_lists_what_changed_new_first_then_improved_then_fixed(): void
    {
        SiteUpdate::query()->delete();
        $update = SiteUpdate::factory()->create(['version' => '1.2', 'title' => 'Appointments', 'released_on' => '2026-10-20', 'items' => [
            ['kind' => 'fixed', 'text' => 'The map opens again'],
            ['kind' => 'new', 'text' => 'Book a visit online', 'text_en' => 'Book online'],
            ['kind' => 'nonsense', 'text' => 'Never shown'],
            ['kind' => 'improved', 'text' => '   '],
            'nonsense',
        ]]);

        $this->assertSame(['new', 'fixed'], array_column($update->changes(), 'kind'));

        $this->travelTo('2026-10-21 09:00');
        $this->get('/updates')
            ->assertSeeInOrder(['v1.2', 'Appointments', '20 Oct 2026', 'New', 'Book online', 'Fixed', 'The map opens again'])
            ->assertDontSee('Never shown');

        app()->setLocale('th');
        $this->assertSame('20 ต.ค. 2569', $update->releasedOn());
        $this->assertSame('Book a visit online', $update->changes()[0]['text']);
    }

    public function test_what_is_being_worked_on_is_shown_apart_and_one_dated_ahead_waits_for_its_day(): void
    {
        SiteUpdate::factory()->planned()->create(['title' => 'Booking a visit online']);
        SiteUpdate::factory()->create(['title' => 'Comes out next week', 'released_on' => now()->addWeek()->toDateString()]);

        $this->get('/updates')
            ->assertSeeInOrder(['Being worked on', 'Booking a visit online', 'Releases', 'v0.3'])
            ->assertDontSee('Comes out next week');

        $this->travel(8)->days();
        $this->get('/updates')->assertSee('Comes out next week');
    }

    public function test_every_page_names_the_newest_release_and_follows_a_new_one_at_once(): void
    {
        $this->get('/')->assertSee('href="'.route('updates').'"', false)->assertSee('v0.3 · updated 12 Oct 2026');

        SiteUpdate::factory()->create(['version' => '1.0', 'released_on' => now()->toDateString()]);

        $this->get('/contact')->assertSee('v1.0 · updated');

        // With none at all, the footer says nothing of it.
        SiteUpdate::query()->get()->each->delete();
        $this->assertNull(SiteUpdate::newest());
        $this->get('/')->assertOk()->assertDontSee('· updated');
        $this->get('/updates')->assertOk()->assertSee('Nothing has been added here yet.');
    }

    public function test_an_administrator_writes_a_release_and_nobody_else_can(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/site-updates')->assertForbidden();

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageSiteUpdates::class)
            ->callAction('create', ['version' => '1.0', 'title' => 'Open to the public', 'status' => SiteUpdate::STATUS_RELEASED, 'released_on' => now()->toDateString(), 'items' => [['kind' => 'new', 'text' => 'The site is open']]])
            ->assertHasNoFormErrors();

        $made = SiteUpdate::where('version', '1.0')->firstOrFail();

        $this->assertSame('The site is open', $made->changes()[0]['text']);
        $this->assertTrue(SiteUpdate::newest()->is($made));
    }

    public function test_the_page_and_the_panel_read_in_thai_and_the_sitemap_lists_the_page(): void
    {
        $this->get('/sitemap.xml')->assertSee(route('updates'));

        app()->setLocale('th');

        $this->get('/updates')->assertSee('มีอะไรใหม่ในเว็บไซต์นี้')->assertSee('ปรับปรุงล่าสุด')->assertSee('ใหม่');
        $this->actingAs(User::factory()->admin()->create())->get('/admin/site-updates')->assertSee('ความคืบหน้าของเว็บไซต์');
    }
}
