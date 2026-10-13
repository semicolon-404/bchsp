<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Department;
use App\Models\SitePage;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** What shows a visitor the site is kept up: "New", the feed, what is read most, when a page last changed, and the numbers page. */
class LivelySiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_what_came_out_this_week_is_marked_new(): void
    {
        $fresh = Announcement::factory()->create(['title' => 'Out yesterday']);
        $older = Announcement::factory()->create(['title' => 'Out last month', 'starts_at' => now()->subMonth()]);
        $draft = Announcement::factory()->create(['status' => Announcement::STATUS_DRAFT]);

        $this->assertTrue($fresh->isNew());
        $this->assertFalse($older->isNew());
        $this->assertFalse($draft->isNew());

        $list = $this->get('/announcements')->assertOk()->getContent();
        $this->assertSame(1, substr_count($list, '>New</span>'));

        $this->get('/announcements?view=cards')->assertSee('>New</span>', false);

        // A week on, it is news no longer.
        $this->travel(8)->days();
        $this->get('/announcements')->assertDontSee('>New</span>', false);
    }

    public function test_the_newest_announcements_are_offered_as_a_feed(): void
    {
        SiteSettings::save(['app.name' => 'Buachet Hospital']);
        $notice = Announcement::factory()->create(['title' => 'Flu vaccine & you', 'body' => '<p>Free for <strong>everyone</strong> over 60.</p>']);
        Announcement::factory()->scheduled()->create(['title' => 'Not out yet']);
        Announcement::factory()->create(['title' => 'Still a draft', 'status' => Announcement::STATUS_DRAFT]);

        $xml = $this->get('/feed')->assertOk()->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8')->getContent();
        $feed = simplexml_load_string($xml);

        $this->assertNotFalse($feed);
        $this->assertSame('Buachet Hospital', (string) $feed->channel->title);
        $this->assertCount(1, $feed->channel->item);
        $this->assertSame('Flu vaccine & you', (string) $feed->channel->item[0]->title);
        $this->assertSame(route('announcements.show', $notice), (string) $feed->channel->item[0]->link);
        $this->assertSame('Free for everyone over 60.', (string) $feed->channel->item[0]->description);

        // Every page says where it is, and the list offers it in words.
        $this->get('/')->assertSee('<link rel="alternate" type="application/rss+xml"', false);
        $this->get('/announcements')->assertSee('href="'.route('feed').'"', false);
    }

    public function test_the_list_names_what_is_read_most_only_as_it_opens(): void
    {
        Announcement::factory()->create(['title' => 'Read a little', 'views' => 5]);
        Announcement::factory()->create(['title' => 'Read the most', 'views' => 900]);
        Announcement::factory()->create(['title' => 'Never opened']);
        Announcement::factory()->create(['title' => 'From long ago', 'views' => 5000, 'starts_at' => now()->subYear()]);

        $this->assertSame(['Read the most', 'Read a little'], Announcement::mostRead()->pluck('title')->all());

        $this->get('/announcements')->assertSeeInOrder(['Most read', 'Read the most', 'opened 900 times', 'Read a little']);
        // Narrowed, the reader is after something already.
        $this->get('/announcements?q=Read')->assertDontSee('Most read');
        $this->get('/announcements?year=today')->assertDontSee('Most read');
    }

    public function test_pages_that_must_be_kept_up_say_when_they_last_changed(): void
    {
        $this->travelTo('2026-11-03 10:00');

        $page = SitePage::factory()->create(['title' => 'History', 'section' => 'about']);
        $department = Department::factory()->create(['name' => 'Dental', 'description' => 'Fillings and extractions.']);

        $this->get(route('pages.show', $page))->assertSeeInOrder(['Last changed', '<time datetime="2026-11-03">3 Nov 2026</time>'], false);
        $this->get(route('departments.show', $department))->assertSeeInOrder(['Last changed', '3 Nov 2026']);

        app()->setLocale('th');
        $this->get(route('pages.show', $page))->assertSee('3 พ.ย. 2569');
    }

    public function test_every_departments_number_is_on_one_page(): void
    {
        SiteSettings::save(['contact.phone' => '044-579076', 'contact.emergency_phone' => '1669']);
        $dental = Department::factory()->create(['name' => 'Dental', 'phone' => '044-579076 ต่อ 120', 'location' => 'Building 2', 'sort_order' => 1]);
        Department::factory()->create(['name' => 'Records']);
        Department::factory()->create(['name' => 'Hidden unit', 'phone' => '999', 'is_active' => false]);

        $this->get('/phones')
            ->assertOk()
            ->assertSeeInOrder(['Phone numbers', 'data-filter="#phone-list"', 'href="tel:1669"', 'href="tel:044579076"', route('departments.show', $dental), 'Dental', 'Building 2', '044-579076 ต่อ 120'], false)
            ->assertDontSee('Records')
            ->assertDontSee('Hidden unit');

        // The contact page and the list of departments lead to it, and so does the sitemap.
        $this->get('/contact')->assertSee('href="'.route('phones').'"', false);
        $this->get('/departments')->assertSee('href="'.route('phones').'"', false);
        $this->get('/sitemap.xml')->assertSee(route('phones'));
    }

    public function test_the_updates_page_counts_this_month_by_itself_and_an_announcement_can_be_printed(): void
    {
        $dental = Department::factory()->create();
        $notice = Announcement::factory()->create(['department_id' => $dental->id]);
        Announcement::factory()->create();
        Announcement::factory()->create(['starts_at' => now()->subMonths(2)]);

        $this->get('/updates')->assertSeeInOrder(['This month on the site', 'data-count>2</dd>', 'Announcements posted', 'data-count>1</dd>', 'Departments that posted'], false);

        $this->get(route('announcements.show', $notice))->assertSee('data-print', false)->assertSee('Print');
    }

    public function test_the_new_words_read_in_thai(): void
    {
        app()->setLocale('th');
        Announcement::factory()->create(['views' => 3]);

        $this->get('/announcements')->assertSee('>ใหม่</span>', false)->assertSee('ติดตามประกาศใหม่ (RSS)')->assertSee('เปิดอ่าน 3 ครั้ง');
        $this->get('/phones')->assertSee('เบอร์โทรศัพท์');
        $this->get('/updates')->assertSee('เดือนนี้บนเว็บไซต์');
    }
}
