<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Department;
use App\Models\ItaItem;
use App\Models\SitePage;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SearchAndSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_finds_a_thai_word_in_announcements_pages_and_departments(): void
    {
        Announcement::factory()->create(['title' => 'ประกาศฉีดวัคซีนไข้หวัดใหญ่ฟรี']);
        Announcement::factory()->create(['title' => 'ความรู้เรื่องภูมิคุ้มกัน', 'body' => '<p>ทำไมต้องฉีดวัคซีน</p>', 'starts_at' => now()->subDays(3)]);
        SitePage::factory()->create(['title' => 'บริการ', 'body' => '<p>คลินิกวัคซีนเปิดทุกวันพุธ</p>']);
        Department::factory()->create(['name' => 'งานสร้างเสริมภูมิคุ้มกัน', 'description' => 'ให้บริการวัคซีนเด็ก']);
        Announcement::factory()->create(['title' => 'ประกาศจัดซื้อยา']);

        $this->get('/search?q='.urlencode('วัคซีน'))
            ->assertOk()
            ->assertSee('4 results for')
            ->assertSeeInOrder(['Announcements', 'ประกาศฉีดวัคซีนไข้หวัดใหญ่ฟรี', 'ความรู้เรื่องภูมิคุ้มกัน', 'Pages', 'บริการ', 'Departments', 'งานสร้างเสริมภูมิคุ้มกัน'])
            ->assertDontSee('ประกาศจัดซื้อยา');
    }

    public function test_what_is_not_out_is_not_found(): void
    {
        Announcement::factory()->scheduled()->create(['title' => 'Secret upcoming notice']);
        SitePage::factory()->create(['title' => 'Secret page', 'is_published' => false]);
        Department::factory()->create(['name' => 'Secret unit', 'is_active' => false]);

        $this->get('/search?q=secret')->assertOk()->assertSee('Nothing was found for')->assertDontSee('Secret upcoming');

        // One that has ended is in the archive, and found.
        Announcement::factory()->ended()->create(['title' => 'Secret ended notice']);
        $this->get('/search?q=secret')->assertSee('1 result for')->assertSee('Secret ended notice');
    }

    public function test_search_asks_for_two_characters_and_treats_wildcards_as_themselves(): void
    {
        Announcement::factory()->create(['title' => 'Plain notice']);
        Announcement::factory()->create(['title' => 'Discount of 50% on check-ups']);

        $this->get('/search')->assertOk()->assertDontSee('results for');
        $this->get('/search?q=a')->assertSee('Type at least 2 characters.');
        $this->get('/search?q='.urlencode('%%'))->assertSee('Nothing was found for');
        $this->get('/search?q='.urlencode('50%'))->assertSee('1 result for')->assertSee('Discount of 50% on check-ups')->assertDontSee('Plain notice');
        $this->get('/search?q[]=x')->assertOk();
    }

    public function test_what_was_typed_is_printed_safely_and_kept_nowhere(): void
    {
        $tables = fn () => collect(['access_logs', 'settings', 'cache'])->mapWithKeys(fn ($table) => [$table => DB::table($table)->count()])->all();
        $before = $tables();

        $page = $this->get('/search?q='.urlencode('"><script>alert(1)</script>'))->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert(1)', $page);
        $this->assertSame($before, $tables());
        $this->assertStringContainsString('<meta name="robots" content="noindex">', $page);
    }

    public function test_robots_txt_keeps_search_engines_out_of_the_panel_and_points_at_the_sitemap(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Disallow: /admin')
            ->assertSee('Disallow: /staff')
            ->assertSee('Sitemap: '.route('sitemap'));
    }

    public function test_the_sitemap_lists_what_is_out_and_nothing_else(): void
    {
        $notice = Announcement::factory()->create();
        $hidden = Announcement::factory()->scheduled()->create();
        $page = SitePage::factory()->create();
        $department = Department::factory()->create();
        ItaItem::factory()->create(['year' => 2569]);
        ItaItem::factory()->create(['year' => 2568]);

        $xml = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();

        $this->assertNotFalse(simplexml_load_string($xml));

        foreach ([route('home'), route('contact'), route('announcements.show', $notice), route('posters'), route('pages.show', $page), route('departments.show', $department), route('ita'), route('ita', ['year' => 2568])] as $url) {
            $this->assertStringContainsString('<loc>'.e($url).'</loc>', $xml);
        }

        $this->assertStringNotContainsString(route('announcements.show', $hidden), $xml);
        $this->assertStringNotContainsString('/blog', $xml);
        $this->assertStringNotContainsString('/staff', $xml);
        $this->assertStringNotContainsString('/admin', $xml);
    }

    public function test_every_page_says_how_it_looks_when_shared_and_what_its_one_address_is(): void
    {
        SiteSettings::save(['app.name' => 'Buachet Hospital']);
        $post = Announcement::factory()->create(['title' => 'Washing hands', 'image_path' => 'announcements/pictures/a.webp']);

        $this->get(route('announcements.show', $post).'?utm_source=x')
            ->assertSee('<link rel="canonical" href="'.route('announcements.show', $post).'">', false)
            ->assertSee('<meta property="og:title" content="Washing hands">', false)
            ->assertSee('<meta property="og:site_name" content="Buachet Hospital">', false)
            ->assertSee('announcements/pictures/a.webp', false)
            ->assertDontSee('application/ld+json', false);
    }

    public function test_the_home_page_tells_search_engines_what_the_hospital_is(): void
    {
        SiteSettings::save(['app.name' => 'Buachet Hospital', 'contact.phone' => '044-579076', 'contact.address' => '221 Moo 4 </script><script>alert(1)</script>']);

        $page = $this->get('/')->assertOk()->getContent();

        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $page, $found);
        $data = json_decode($found[1], true);

        $this->assertSame('Hospital', $data['@type']);
        $this->assertSame('Buachet Hospital', $data['name']);
        $this->assertSame('044-579076', $data['telephone']);
        $this->assertStringContainsString('221 Moo 4', $data['address']['streetAddress']);
        // What an administrator typed cannot end the block and start a script of its own.
        $this->assertStringNotContainsString('<script>alert(1)', $page);
    }

    public function test_the_search_page_is_reached_from_every_page_and_reads_in_thai(): void
    {
        app()->setLocale('th');

        $this->get('/')->assertSee('href="'.route('search').'"', false);
        $this->get('/search?q='.urlencode('ไม่มีคำนี้'))->assertSee('ค้นหาในเว็บไซต์')->assertSee('ไม่พบผลลัพธ์สำหรับ');
    }
}
