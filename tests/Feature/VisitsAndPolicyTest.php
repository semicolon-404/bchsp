<?php

namespace Tests\Feature;

use App\Models\SitePage;
use App\Models\User;
use App\Support\Visits;
use Database\Seeders\PolicyPageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        return (int) DB::table('site_visits')->where('day', now()->toDateString())->value('views');
    }

    public function test_each_page_a_person_opens_adds_one_to_todays_count(): void
    {
        $this->get('/', self::BROWSER)->assertOk();
        $this->get('/contact', self::BROWSER)->assertOk();
        $this->get('/announcements', self::BROWSER)->assertOk();

        $this->assertSame(3, $this->today());
        $this->assertSame(1, DB::table('site_visits')->count());
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

        $this->assertSame(['day', 'views'], Schema::getColumnListing('site_visits'));
        $this->assertStringNotContainsString('203.0.113.9', json_encode(DB::table('site_visits')->get()));
    }

    public function test_the_footer_shows_the_totals(): void
    {
        DB::table('site_visits')->insert([['day' => now()->toDateString(), 'views' => 1234], ['day' => now()->subMonths(2)->toDateString(), 'views' => 10000]]);

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
