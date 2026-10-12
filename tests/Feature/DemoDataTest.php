<?php

namespace Tests\Feature;

use App\Models\AccessLog;
use App\Models\Announcement;
use App\Models\Document;
use App\Models\ItaItem;
use App\Models\User;
use App\Support\DemoData;
use App\Support\Reports;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        DemoData::$manifestAt = storage_path('framework/testing/demo.json');
        File::ensureDirectoryExists(dirname(DemoData::$manifestAt));
        File::delete(DemoData::manifest());
    }

    protected function tearDown(): void
    {
        File::delete(DemoData::manifest());
        DemoData::$manifestAt = null;

        parent::tearDown();
    }

    public function test_the_sample_content_fills_every_part_of_the_site(): void
    {
        User::factory()->admin()->create();

        $this->artisan('demo:fill')->assertSuccessful();

        // Posters upright and wide, some among the slides, one of them opening over the page.
        $this->assertNotNull(Announcement::popup());
        $this->assertGreaterThan(10, Announcement::posters()->count());

        // Announcements of every type over more than two fiscal years, in every state the list has to show.
        $this->assertGreaterThan(60, Announcement::count());
        $this->assertGreaterThanOrEqual(3, count(Announcement::fiscalYears()));
        $this->assertTrue(Announcement::where('is_pinned', true)->exists());
        $this->assertTrue(Announcement::where('starts_at', '>', now())->exists());
        $this->assertTrue(Announcement::whereNotNull('ends_at')->where('ends_at', '<', now())->exists());
        $this->assertTrue(Announcement::whereNotNull('image_path')->exists());
        $this->assertTrue(Announcement::whereNotNull('attachments')->exists());
        $this->assertTrue(Announcement::whereNotNull('links')->exists());
        $this->assertGreaterThan(3, Announcement::slides()->count());

        // What articles were: health knowledge, and activities with their albums.
        $this->assertSame(4, Announcement::whereRelation('type', 'slug', 'activities')->count());
        $this->assertTrue(Announcement::whereRelation('type', 'slug', 'knowledge')->exists());
        $this->assertSame(3, Announcement::whereNotNull('gallery')->count());
        $this->assertSame(46, ItaItem::count());
        $this->assertSame(6, Document::count());
        $this->assertGreaterThan(1000, DB::table('site_visits')->sum('views'));
        $this->assertGreaterThan(50, AccessLog::count());

        // Every picture and file it speaks of is really there.
        foreach (Announcement::whereNotNull('image_path')->pluck('image_path') as $path) {
            Storage::disk('public')->assertExists($path);
        }
        $this->assertStringStartsWith('%PDF-1.4', Storage::disk('public')->get(Document::first()->file_path));

        // Everything says that it is a sample.
        $this->assertSame(0, Announcement::where('title', 'not like', DemoData::MARK.'%')->count());
    }

    public function test_every_page_opens_with_the_sample_content_in(): void
    {
        User::factory()->admin()->create();
        (new DemoData)->fill();

        $notice = Announcement::whereNotNull('image_path')->first();
        $post = Announcement::whereNotNull('gallery')->first();
        $year = array_key_first(Announcement::fiscalYears());

        foreach (['/', '/announcements', '/announcements?type=procurement&year='.$year, '/announcements?page=3', route('announcements.show', $notice),
            '/posters', '/announcements?type=knowledge&view=cards', route('announcements.show', $post), '/ita', '/ita/'.ItaItem::min('year'), '/downloads', '/menu', '/search?q='.urlencode('ตัวอย่าง'), '/sitemap.xml'] as $address) {
            $this->get($address)->assertOk();
        }

        $this->actingAs(User::first());

        foreach (['/admin', '/admin/reports', '/admin/announcements', '/admin/announcement-types', '/admin/ita-items', '/admin/documents', '/admin/access-logs'] as $address) {
            $this->get($address)->assertOk();
        }

        $this->get(route('reports.print', ['period' => 'year', 'year' => $year]))->assertOk()->assertSee(DemoData::MARK);

        [$from, $until] = Reports::period(['period' => 'year', 'year' => $year - 1]);
        $summary = Reports::summary($from, $until);
        $this->assertGreaterThan(20, $summary['announcements']['total']);
        $this->assertSame(100, Reports::ita($year - 1)['percent']);
        $this->assertGreaterThan(0, Reports::ita($year)['percent']);
        $this->assertLessThan(100, Reports::ita($year)['percent']);
    }

    public function test_clearing_takes_out_the_sample_content_and_leaves_real_content_alone(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'The real administrator']);
        $real = Announcement::factory()->create(['title' => 'A real announcement']);
        Storage::disk('public')->put('announcements/files/real.pdf', 'real');
        DB::table('site_visits')->insert(['day' => now()->subDays(5)->toDateString(), 'section' => 'home', 'views' => 7]);
        AccessLog::record('login', userId: $admin->id);

        $this->artisan('demo:fill')->assertSuccessful();
        // Real content entered while the samples are in is not theirs either.
        $later = Announcement::factory()->create(['title' => 'A real article written later']);

        $this->artisan('demo:clear')->assertSuccessful();

        $this->assertSame(['A real announcement', 'A real article written later'], Announcement::orderBy('id')->pluck('title')->all());
        $this->assertSame(['The real administrator'], User::pluck('name')->all());
        $this->assertSame(0, ItaItem::count() + Document::count());
        $this->assertSame(1, AccessLog::count());
        $this->assertSame(7, (int) DB::table('site_visits')->sum('views'));
        $this->assertSame(['announcements/files/real.pdf'], Storage::disk('public')->allFiles());
        $this->assertFalse(DemoData::isFilled());

        $this->artisan('demo:clear')->expectsOutputToContain('no sample content')->assertSuccessful();
    }

    public function test_the_tests_do_not_touch_the_list_of_the_sites_own_sample_content(): void
    {
        $this->assertNotSame(storage_path('app/demo.json'), DemoData::manifest());
    }

    public function test_it_is_not_put_in_twice_and_not_on_a_live_site_without_being_told(): void
    {
        User::factory()->admin()->create();

        $this->artisan('demo:fill')->assertSuccessful();
        $count = Announcement::count();

        $this->artisan('demo:fill')->expectsOutputToContain('already in')->assertFailed();
        $this->assertSame($count, Announcement::count());

        $this->artisan('demo:clear');
        $this->app['env'] = 'production';

        $this->artisan('demo:fill')->expectsOutputToContain('live site')->assertFailed();
        $this->assertSame(0, Announcement::count());
    }
}
