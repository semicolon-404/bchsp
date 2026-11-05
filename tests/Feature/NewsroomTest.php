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

        $this->get('/announcements')->assertOk()->assertSeeInOrder(['data-count>3</dd>', 'In all', 'data-count>1</dd>', 'This week', 'data-count>2</dd>', 'This month'], false);
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

    public function test_the_newest_are_set_large_only_as_the_whole_list_opens_and_only_when_there_are_enough(): void
    {
        Announcement::factory()->create(['title' => 'First of three']);
        Announcement::factory()->count(2)->create();

        // With three, the list itself says them all: nothing is set large above it.
        $this->get('/announcements')->assertDontSee('id="latest-heading"', false);

        Announcement::factory()->create(['title' => 'The newest, with a picture', 'starts_at' => now()->subMinute(), 'image_path' => 'announcements/pictures/big.webp']);

        $page = $this->get('/announcements')->assertSee('id="latest-heading"', false)->assertSeeInOrder(['Latest', 'The newest, with a picture', 'Types'], false)->getContent();

        // Set large it carries no marks: "New" is said once, in the list.
        $this->assertSame(4, substr_count($page, '>New</span>'));
        // Set large, in the list (and its share button), and as "last updated" in the band.
        $this->assertSame(4, substr_count($page, 'The newest, with a picture'));

        // Narrowed, or on a later page, there is the list alone.
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
        $this->get('/announcements')->assertSee('ล่าสุด', false)->assertSee('รวมทั้งหมด');
    }
}
