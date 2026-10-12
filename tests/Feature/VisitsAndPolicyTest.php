<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\AnnouncementType;
use App\Models\SitePage;
use App\Models\User;
use App\Support\Visits;
use Database\Seeders\PolicyPageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VisitsAndPolicyTest extends TestCase
{
    use RefreshDatabase;

    private const BROWSER = ['User-Agent' => 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 Chrome/126 Mobile Safari/537.36'];

    private function today(): int
    {
        return (int) DB::table('site_visits')->where('day', now()->toDateString())->sum('views');
    }

    public function test_each_page_a_person_opens_adds_one_to_todays_count(): void
    {
        $this->get('/', self::BROWSER)->assertOk();
        $this->get('/contact', self::BROWSER)->assertOk();
        $this->get('/announcements', self::BROWSER)->assertOk();

        $this->assertSame(3, $this->today());
        // One row for each part of the site that was opened today: home, contact, news.
        $this->assertSame(3, DB::table('site_visits')->count());
    }

    public function test_a_new_day_starts_a_new_count_and_the_totals_add_up(): void
    {
        $this->get('/', self::BROWSER);
        $this->travel(1)->days();
        $this->get('/', self::BROWSER);
        $this->get('/', self::BROWSER);

        Cache::flush();

        $this->assertSame(2, Visits::totals()['today']);
        $this->assertSame(3, Visits::totals()['all']);
        $this->assertSame(2, DB::table('site_visits')->count());
    }

    public function test_search_engines_staff_the_panel_and_pages_that_did_not_open_are_not_counted(): void
    {
        $this->get('/', ['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)']);
        $this->get('/', ['User-Agent' => '']);
        $this->get('/no-such-page', self::BROWSER)->assertNotFound();
        $this->get('/admin/login', self::BROWSER)->assertOk();
        $this->get('/robots.txt', self::BROWSER)->assertOk();
        $this->get('/sitemap.xml', self::BROWSER)->assertOk();
        $this->get('/language/en', self::BROWSER);
        $this->actingAs(User::factory()->create())->get('/', self::BROWSER)->assertOk();

        $this->assertSame(0, $this->today());
    }

    public function test_nothing_is_kept_that_could_be_traced_to_a_person(): void
    {
        $this->get('/contact?secret=1', self::BROWSER + ['REMOTE_ADDR' => '203.0.113.9']);

        $this->assertSame(['day', 'section', 'views'], Schema::getColumnListing('site_visits'));
        $this->assertStringNotContainsString('203.0.113.9', json_encode(DB::table('site_visits')->get()));
    }

    public function test_each_part_of_the_site_is_counted_on_its_own(): void
    {
        $notice = Announcement::factory()->create();
        $bid = Announcement::factory()->for(AnnouncementType::where('slug', 'procurement')->first(), 'type')->create();
        $page = SitePage::factory()->create();

        foreach (['/', '/', '/announcements', route('announcements.show', $notice), '/announcements?type=procurement', route('announcements.show', $bid),
            '/posters', '/ita', '/schedule', '/departments', route('pages.show', $page), '/about', '/contact', '/downloads', '/search?q=xx', '/menu'] as $address) {
            $this->get($address, self::BROWSER)->assertOk();
        }

        $today = now()->startOfDay();

        // Compared whatever their order: parts opened equally often come back in no particular one.
        $this->assertEquals(
            ['home' => 2, 'news' => 3, 'procurement' => 2, 'pages' => 2, 'ita' => 1, 'schedule' => 1, 'departments' => 1, 'contact' => 1, 'downloads' => 1, 'search' => 1, 'other' => 1],
            Visits::bySection($today, $today),
        );
        $this->assertSame(16, Visits::between($today, $today));
        // Every part the site counts has a name for the reports.
        $this->assertSame([], array_diff(array_keys(Visits::bySection($today, $today)), array_keys(Visits::SECTIONS)));
    }

    public function test_each_announcement_counts_how_often_it_was_opened_without_looking_changed(): void
    {
        $notice = Announcement::factory()->create();
        $this->travel(1)->hours();

        $this->get(route('announcements.show', $notice), self::BROWSER);
        $this->get(route('announcements.show', $notice), self::BROWSER);
        // A search engine, and staff checking their own work, are not readers.
        $this->get(route('announcements.show', $notice), ['User-Agent' => 'Googlebot']);
        $this->actingAs(User::factory()->create())->get(route('announcements.show', $notice), self::BROWSER);

        $this->assertSame(2, (int) $notice->fresh()->views);
        $this->assertTrue($notice->fresh()->updated_at->equalTo($notice->updated_at));
    }

    public function test_days_and_months_with_nothing_counted_are_still_listed(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 12:00', 'Asia/Bangkok'));
        DB::table('site_visits')->insert([
            ['day' => '2026-10-08', 'section' => 'home', 'views' => 5],
            ['day' => '2026-10-08', 'section' => 'news', 'views' => 2],
            ['day' => '2026-10-10', 'section' => 'home', 'views' => 3],
            ['day' => '2026-08-15', 'section' => 'home', 'views' => 40],
        ]);

        $this->assertSame(['2026-10-08' => 7, '2026-10-09' => 0, '2026-10-10' => 3], Visits::daily(now()->subDays(2), now()));
        $this->assertSame(['2026-08' => 40, '2026-09' => 0, '2026-10' => 10], Visits::monthly(Carbon::parse('2026-08-01'), now()));
    }

    public function test_the_footer_shows_the_totals(): void
    {
        DB::table('site_visits')->insert([['day' => now()->toDateString(), 'section' => 'home', 'views' => 1000], ['day' => now()->toDateString(), 'section' => 'news', 'views' => 234], ['day' => now()->subMonths(2)->toDateString(), 'section' => 'home', 'views' => 10000]]);

        $this->get('/')->assertSee('Pages opened: today 1,234 · this month 1,234 · in all 11,234');
    }

    public function test_the_privacy_page_says_what_the_site_really_does_and_is_linked_from_every_footer(): void
    {
        $this->seed(PolicyPageSeeder::class);

        $this->get('/p/privacy')
            ->assertOk()
            ->assertSee('เว็บไซต์นี้ไม่เก็บข้อมูลส่วนบุคคลของผู้เข้าชม')
            ->assertSee('Google Maps')
            ->assertSee('สถิติการเข้าชม');

        $this->get('/')->assertSee(route('pages.show', 'privacy'), false);
    }

    public function test_the_privacy_page_is_added_once_and_what_the_hospital_rewrote_is_kept(): void
    {
        $this->seed(PolicyPageSeeder::class);
        SitePage::where('slug', 'privacy')->update(['title' => 'Rewritten by the hospital']);

        $this->seed(PolicyPageSeeder::class);

        $this->assertSame(1, SitePage::where('slug', 'privacy')->count());
        $this->assertSame('Rewritten by the hospital', SitePage::where('slug', 'privacy')->value('title'));
    }
}
