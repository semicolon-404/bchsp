<?php

namespace Tests\Feature;

use App\Filament\Pages\Reports as ReportsPage;
use App\Filament\Widgets\NeedsAttention;
use App\Filament\Widgets\SiteStats;
use App\Filament\Widgets\VisitsChart;
use App\Models\AccessLog;
use App\Models\Announcement;
use App\Models\AnnouncementType;
use App\Models\ItaItem;
use App\Models\User;
use App\Support\Reports;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ReportsAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The middle of fiscal year 2569 (October 2025 to September 2026).
        $this->travelTo(Carbon::parse('2026-03-20 10:00', 'Asia/Bangkok'));
    }

    private function type(string $slug): AnnouncementType
    {
        return AnnouncementType::where('slug', $slug)->firstOrFail();
    }

    /** A year of the site: announcements, pages opened, ITA and what staff did. */
    private function aYearOfTheSite(): User
    {
        $malee = User::factory()->create(['name' => 'Malee']);

        Announcement::factory()->for($this->type('procurement'), 'type')->create(['title' => 'Winning bid, November', 'starts_at' => '2025-11-10 09:00', 'views' => 40]);
        Announcement::factory()->for($this->type('procurement'), 'type')->create(['title' => 'Winning bid, February', 'starts_at' => '2026-02-10 09:00', 'views' => 5]);
        Announcement::factory()->for($this->type('news'), 'type')->create(['title' => 'Flu vaccine week', 'starts_at' => '2026-02-15 09:00', 'views' => 120]);
        Announcement::factory()->for($this->type('news'), 'type')->create(['title' => 'From the year before', 'starts_at' => '2025-08-01 09:00', 'views' => 999]);
        Announcement::factory()->for($this->type('news'), 'type')->create(['title' => 'Not out yet', 'starts_at' => '2026-05-01 09:00']);

        DB::table('site_visits')->insert([
            ['day' => '2025-11-03', 'section' => 'home', 'views' => 100],
            ['day' => '2026-02-11', 'section' => 'procurement', 'views' => 300],
            ['day' => '2026-02-12', 'section' => 'home', 'views' => 50],
            ['day' => '2025-08-01', 'section' => 'home', 'views' => 7000],
        ]);

        ItaItem::factory()->create(['year' => 2569, 'number' => 1, 'code' => 'MOIT 1', 'links' => [['label' => 'x', 'url' => 'https://example.com/a']]]);
        ItaItem::factory()->create(['year' => 2569, 'number' => 2, 'code' => 'MOIT 2']);
        ItaItem::factory()->create(['year' => 2569, 'number' => 3, 'code' => 'MOIT 3']);

        foreach (['admin.created' => 3, 'admin.updated' => 2, 'admin.deleted' => 1, 'login' => 4] as $event => $times) {
            for ($i = 0; $i < $times; $i++) {
                AccessLog::create(['user_id' => $malee->id, 'event' => $event]);
            }
        }
        AccessLog::query()->update(['created_at' => '2026-02-20 09:00']);

        return $malee;
    }

    public function test_a_fiscal_year_a_quarter_and_a_month_are_the_right_days(): void
    {
        [$from, $until, $name] = Reports::period(['period' => 'year', 'year' => 2569]);
        $this->assertSame(['2025-10-01', '2026-09-30', 'Fiscal year 2569'], [$from->toDateString(), $until->toDateString(), $name]);

        [$from, $until] = Reports::period(['period' => 'quarter', 'year' => 2569, 'quarter' => 2]);
        $this->assertSame(['2026-01-01', '2026-03-31'], [$from->toDateString(), $until->toDateString()]);

        // November of fiscal year 2569 is in the calendar year before; February is not.
        [$from, $until] = Reports::period(['period' => 'month', 'year' => 2569, 'month' => 11]);
        $this->assertSame(['2025-11-01', '2025-11-30'], [$from->toDateString(), $until->toDateString()]);
        [$from, $until] = Reports::period(['period' => 'month', 'year' => 2569, 'month' => 2]);
        $this->assertSame(['2026-02-01', '2026-02-28'], [$from->toDateString(), $until->toDateString()]);

        // Nonsense falls back to something sensible rather than failing.
        [$from] = Reports::period(['period' => 'month', 'year' => 2569, 'month' => 99]);
        $this->assertSame('2025-12-01', $from->toDateString());
    }

    public function test_the_report_of_a_fiscal_year_adds_up_what_happened_in_it_and_nothing_else(): void
    {
        $this->aYearOfTheSite();
        [$from, $until] = Reports::period(['period' => 'year', 'year' => 2569]);

        $summary = Reports::summary($from, $until);

        $this->assertSame(450, $summary['visits']['total']);
        $this->assertSame(['procurement' => 300, 'home' => 150], $summary['visits']['sections']);
        $this->assertSame(100, $summary['visits']['monthly']['2025-11']);
        $this->assertSame(0, $summary['visits']['monthly']['2025-12']);
        // Months that have not come yet are not listed as empty.
        $this->assertArrayNotHasKey('2026-04', $summary['visits']['monthly']);

        $this->assertSame(3, $summary['announcements']['total']);
        $this->assertSame(['ข่าวประชาสัมพันธ์' => 1, 'จัดซื้อจัดจ้าง/ประกวดราคา' => 2, 'รับสมัครงาน' => 0, 'ความรู้สุขภาพ' => 0, 'ข่าวกิจกรรม' => 0], $summary['announcements']['types']);
        $this->assertSame(['Flu vaccine week', 'Winning bid, November', 'Winning bid, February'], $summary['announcements']['top']->pluck('title')->all());

        $this->assertSame(['year' => 2569, 'items' => 3, 'with_evidence' => 1, 'percent' => 33, 'missing' => ['MOIT 2', 'MOIT 3']], $summary['ita']);
    }

    public function test_the_report_says_what_each_member_of_staff_did_and_lists_idle_accounts_too(): void
    {
        $malee = $this->aYearOfTheSite();
        User::factory()->create(['name' => 'Idle account']);
        [$from, $until] = Reports::period(['period' => 'quarter', 'year' => 2569, 'quarter' => 2]);

        $staff = collect(Reports::staff($from, $until))->keyBy('name');

        $this->assertSame(['created' => 3, 'changed' => 2, 'deleted' => 1, 'sign_ins' => 4], array_intersect_key($staff['Malee'], array_flip(['created', 'changed', 'deleted', 'sign_ins'])));
        $this->assertSame('2026-02-20', $staff['Malee']['last_sign_in']->toDateString());
        $this->assertSame(0, $staff['Idle account']['created']);
        $this->assertNull($staff['Idle account']['last_sign_in']);
        // The busiest first.
        $this->assertSame('Malee', Reports::staff($from, $until)[0]['name']);

        // Another quarter: nothing of hers falls in it.
        [$from, $until] = Reports::period(['period' => 'quarter', 'year' => 2569, 'quarter' => 1]);
        $this->assertSame(0, collect(Reports::staff($from, $until))->firstWhere('name', 'Malee')['created']);
    }

    public function test_an_administrator_reads_the_report_and_changes_its_period(): void
    {
        $this->aYearOfTheSite();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ReportsPage::class)
            ->assertSee('Fiscal year 2569')
            ->assertSee('Flu vaccine week')
            ->assertSee('1 of 3 items have documents')
            ->assertSee('Malee')
            ->fillForm(['period' => 'month', 'month' => 11])
            ->assertSee('November 2568')
            ->assertSee('Winning bid, November')
            ->assertDontSee('Flu vaccine week');
    }

    public function test_only_an_administrator_reaches_the_report_in_any_of_its_forms(): void
    {
        $this->get('/admin/reports-print')->assertForbidden();
        $this->get('/admin/reports-download')->assertForbidden();

        $this->actingAs(User::factory()->create());
        $this->get('/admin/reports')->assertForbidden();
        $this->get('/admin/reports-print')->assertForbidden();
        $this->get('/admin/reports-download')->assertForbidden();

        $this->actingAs(User::factory()->admin()->create());
        $this->get('/admin/reports')->assertOk();
    }

    public function test_the_report_can_be_printed_as_a_page_of_its_own(): void
    {
        $this->aYearOfTheSite();
        $this->actingAs(User::factory()->admin()->create(['name' => 'The administrator']));

        $this->get(route('reports.print', ['period' => 'year', 'year' => 2569]))
            ->assertOk()
            ->assertSee('Website report')
            ->assertSee('Fiscal year 2569')
            ->assertSee('01/10/2568 – 30/09/2569')
            ->assertSee('The administrator')
            ->assertSee('Flu vaccine week')
            ->assertSee('window.print()', false)
            ->assertSee('<meta name="robots" content="noindex">', false);
    }

    public function test_the_report_downloads_as_a_file_excel_opens_with_thai_intact(): void
    {
        $this->aYearOfTheSite();
        Announcement::factory()->for($this->type('news'), 'type')->create(['title' => '=HYPERLINK("http://evil.example","กดที่นี่")', 'starts_at' => '2026-03-01 09:00', 'views' => 1]);
        $this->actingAs(User::factory()->admin()->create());

        $response = $this->get(route('reports.download', ['period' => 'year', 'year' => 2569]))->assertOk();
        $file = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $file);
        $this->assertStringContainsString('report-20251001-20260930.csv', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('จัดซื้อจัดจ้าง/ประกวดราคา,2', $file);
        $this->assertStringContainsString('"Flu vaccine week"', $file);
        $this->assertStringContainsString('Malee', $file);
        // A title that begins like a formula is not one Excel will run.
        $this->assertStringContainsString("\"'=HYPERLINK", $file);
    }

    public function test_the_panels_first_page_shows_how_the_site_is_doing(): void
    {
        $this->aYearOfTheSite();
        DB::table('site_visits')->insert([['day' => now()->toDateString(), 'section' => 'home', 'views' => 12], ['day' => now()->subDay()->toDateString(), 'section' => 'news', 'views' => 8]]);
        $this->actingAs(User::factory()->create());

        $this->get('/admin')->assertOk();

        Livewire::test(SiteStats::class)->assertSee('Pages opened today')->assertSee('12')->assertSee('Pages opened this month')->assertSee('20');
        Livewire::test(VisitsChart::class)->assertSee('Pages opened, the last 30 days');
    }

    public function test_the_first_page_lists_what_is_waiting_for_someone(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(NeedsAttention::class)->assertSee('Nothing is waiting.');

        $this->aYearOfTheSite();
        Announcement::factory()->create(['starts_at' => now()->subDay(), 'ends_at' => now()->addDays(3)]);
        SiteSettings::save(['alert.enabled' => true, 'alert.text' => 'Dental clinic closed today']);

        Livewire::test(NeedsAttention::class)
            ->assertSee('1 announcement ends within a week')
            ->assertSee('1 announcement is waiting for its date')
            ->assertSee('2 ITA items of this year have no documents yet')
            ->assertSee('Dental clinic closed today')
            // What is wrong with the server is the administrator's to see.
            ->assertDontSee('System status');

        $this->actingAs(User::factory()->admin()->create());
        Livewire::test(NeedsAttention::class)->assertSee('wrong on System status');
    }

    public function test_the_report_and_the_first_page_read_in_thai(): void
    {
        app()->setLocale('th');
        $this->aYearOfTheSite();
        $this->actingAs(User::factory()->admin()->create());

        $this->get('/admin/reports')->assertSee('รายงานเว็บไซต์')->assertSee('ปีงบประมาณ 2569')->assertSee('พิมพ์หรือบันทึกเป็น PDF')->assertSee('การทำงานของเจ้าหน้าที่ในหลังบ้าน');
        Livewire::test(NeedsAttention::class)->assertSee('สิ่งที่ต้องดำเนินการ');
    }

    public function test_the_report_sums_up_the_complaints_for_executives_without_who_sent_them(): void
    {
        $this->travelTo('2026-03-15 10:00');
        \App\Models\Complaint::factory()->create(['kind' => 'conduct', 'title' => 'Rude at the counter', 'created_at' => '2026-02-01 09:00', 'is_anonymous' => false, 'name' => 'Somsri Secret', 'phone' => '0899999999']);
        \App\Models\Complaint::factory()->create(['kind' => 'service', 'status' => 'done', 'created_at' => '2026-03-01 09:00', 'closed_at' => '2026-03-03 09:00']);
        \App\Models\Complaint::factory()->create(['kind' => 'praise', 'created_at' => '2025-01-01 09:00']);

        $summary = \App\Support\Reports::complaints(now()->setDate(2025, 10, 1)->startOfDay(), now());
        $this->assertSame([2, 1, 1, 1], [$summary['total'], $summary['done'], $summary['open'], $summary['late']]);
        $this->assertSame(2.0, (float) $summary['average_days']);
        $this->assertSame(1, $summary['kinds']['conduct']);

        $this->actingAs(User::factory()->admin()->create());
        $this->get(route('reports.print', ['period' => 'year', 'year' => 2569]))->assertOk()
            ->assertSee('Complaints and suggestions')->assertSee('To follow up')
            ->assertDontSee('Somsri Secret')->assertDontSee('0899999999')->assertDontSee('Rude at the counter');
    }
}
