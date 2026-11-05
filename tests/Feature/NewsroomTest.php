<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\AnnouncementType;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** /announcements as it opens: figures, the newest set large, a tile for each kind, and ways in beside the list. */
class NewsroomTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_band_at_the_top_says_how_much_there_is_and_how_much_is_new(): void
    {
        $this->travelTo('2026-11-18 09:00');    // a Wednesday

        Announcement::factory()->create(['starts_at' => '2026-11-17 09:00']);
        Announcement::factory()->create(['starts_at' => '2026-11-03 09:00']);
        Announcement::factory()->create(['starts_at' => '2026-09-01 09:00']);
        Announcement::factory()->create(['status' => Announcement::STATUS_DRAFT]);

        $this->get('/announcements')->assertOk()->assertSee('3 announcements · <span class="text-brand">1 new this week</span>', false);
    }

    public function test_each_kind_has_a_tile_that_says_how_many_it_holds(): void
    {
        $news = AnnouncementType::where('slug', 'news')->firstOrFail();
        $jobs = AnnouncementType::where('slug', 'jobs')->firstOrFail();

        Announcement::factory()->count(2)->for($news, 'type')->create();
        Announcement::factory()->for($jobs, 'type')->create();
        Announcement::factory()->scheduled()->for($jobs, 'type')->create();

        $page = $this->get('/announcements')->assertOk()->getContent();

        // "All" with everything out, then each kind in order with its own count; one with none says 0.
        $this->assertMatchesRegularExpression('~>All</span>\s*<span[^>]*>3</span>~', $page);
        $this->assertMatchesRegularExpression('~>News</span>\s*<span[^>]*>2</span>~', $page);
        $this->assertMatchesRegularExpression('~>Job openings</span>\s*<span[^>]*>1</span>~', $page);
        $this->assertMatchesRegularExpression('~>Procurement</span>\s*<span[^>]*>0</span>~', $page);

        // The tile of the kind being looked at is marked.
        $this->get('/announcements?type=jobs')->assertSee('aria-current="page"', false)->assertSee('1 announcement');
    }

    public function test_the_newest_are_set_large_above_the_list_and_left_out_of_it(): void
    {
        Announcement::factory()->count(5)->create(['starts_at' => now()->subDay()]);

        // With five, the list itself says them all: nothing is set large above it.
        $this->get('/announcements')->assertDontSee('id="latest-heading"', false);

        Announcement::factory()->create(['title' => 'The newest, with a picture', 'starts_at' => now()->subMinute(), 'image_path' => 'announcements/pictures/big.webp']);

        // Set large it leads, and the list below does not say it again.
        $page = $this->get('/announcements')->assertSee('id="latest-heading"', false)->assertSee('Earlier')->getContent();
        $this->assertSame(1, substr_count($page, 'The newest, with a picture'));

        // One kind at a time is set large too, with what that kind holds.
        $news = AnnouncementType::where('slug', 'news')->firstOrFail();
        Announcement::query()->update(['announcement_type_id' => $news->id]);
        $this->get('/announcements?type=news')->assertSee('id="latest-heading"', false);

        // Searched, or on a later page, there is the list alone.
        $this->get('/announcements?q=newest')->assertDontSee('id="latest-heading"', false);
        $this->get('/announcements?page=2')->assertDontSee('id="latest-heading"', false);
    }

    public function test_beside_the_list_are_what_is_coming_up_and_who_has_been_posting(): void
    {
        $this->travelTo('2026-11-10 09:00');

        $dental = Department::factory()->create(['name' => 'Dental']);
        Announcement::factory()->create(['title' => 'Blood drive', 'event_on' => '2026-11-18', 'department_id' => $dental->id]);
        Announcement::factory()->create(['title' => 'Already happened', 'event_on' => '2026-11-02']);

        $page = $this->get('/announcements')->assertOk()->assertSeeInOrder(['Coming up', 'Blood drive', 'From the departments', 'Dental'])->getContent();

        // What is over is on the calendar, not under "Coming up".
        $this->assertStringNotContainsString('Already happened', str($page)->between('id="coming-heading"', 'id="posting-heading"')->toString());
        // A department leads to the list narrowed to it.
        $this->assertStringContainsString('department='.$dental->slug, $page);

        $this->get('/announcements?department='.$dental->slug)->assertDontSee('Coming up')->assertSee('1 announcement');
    }

    public function test_a_row_says_how_the_text_begins_and_who_put_it_out_and_nothing_found_says_so(): void
    {
        $dental = Department::factory()->create(['name' => 'Dental']);
        Announcement::factory()->create(['title' => 'Brush twice a day', 'body' => '<p>Morning and <strong>night</strong>,&nbsp;two minutes each.</p>', 'department_id' => $dental->id]);

        $this->get('/announcements')->assertSeeInOrder(['Dental', 'Brush twice a day', 'Morning and night, two minutes each.']);

        $this->get('/announcements?q=zzzz')->assertSee('No announcements match.')->assertSee('Nothing matches. Try another word, or clear the filters.');

        app()->setLocale('th');
        $this->get('/announcements')->assertSee('ตัวกรอง')->assertSee('1 ประกาศ');
    }
}
