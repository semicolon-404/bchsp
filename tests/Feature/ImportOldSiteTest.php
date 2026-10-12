<?php

namespace Tests\Feature;

use App\Models\Announcement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Bringing the old website's announcements in: titles, days, words and document links; no pictures; nothing twice. */
class ImportOldSiteTest extends TestCase
{
    use RefreshDatabase;

    private const NEWS = '/bc/index.php/khawsar-prachasamphanth/khawsar-pra-samphanth';

    private const BIDS = '/bc/index.php/khawsar-prachasamphanth/cad-sux-cad-cang-khaw-prakwd-rakha';

    private string $file;

    protected function setUp(): void
    {
        parent::setUp();

        // A file of the tests' own: what a real import remembered is never touched.
        $this->file = sys_get_temp_dir().DIRECTORY_SEPARATOR.'bchsp-old-site-test-'.getmypid().'.json';
        config(['bchsp.old_site_file' => $this->file]);
        File::delete($this->file);
    }

    protected function tearDown(): void
    {
        File::delete($this->file);

        parent::tearDown();
    }

    /** A page of the old site as it writes one. */
    private function article(string $title, string $day, string $text): string
    {
        return '<html><body><div class="uk-article"><meta property="datePublished" content="'.$day.'">'
            .'<h1 class="uk-article-title" itemprop="headline"> '.$title.' </h1><div class="uk-margin-top" property="text">'.$text.'</div></div></body></html>';
    }

    private function oldSite(): void
    {
        $list = fn (string $section, array $names) => '<table>'.implode('', array_map(fn (string $name) => '<td class="list-title"><a href="'.$section.'/'.$name.'">x</a></td>', $names)).'</table>';

        Http::fake([
            '*'.self::NEWS.'?start=0' => Http::response($list(self::NEWS, ['hiring-a-nurse', 'well-done'])),
            '*'.self::NEWS.'?start=10' => Http::response($list(self::NEWS, ['well-done'])),
            '*'.self::BIDS.'?start=0' => Http::response($list(self::BIDS, ['winning-bid'])),
            '*'.self::BIDS.'?start=10' => Http::response('<table></table>'),
            '*'.self::NEWS.'/hiring-a-nurse' => Http::response($this->article(
                'เปิดรับสมัครคัดเลือกบุคคล ตำแหน่งพยาบาลวิชาชีพ', '2026-09-08T07:07:25+00:00',
                '<p><a href="https://drive.google.com/file/d/ABC/view?usp=sharing">รายละเอียดการรับสมัคร<br><img src="https://example.org/poster.jpg" alt="คำบรรยายภาพยาวมาก"></a></p><p><a href="javascript:alert(1)">bad</a></p>'
            )),
            '*'.self::NEWS.'/well-done' => Http::response($this->article(
                'ขอแสดงความยินดีกับบุคลากรดีเด่น', '2026-09-01T03:00:00+00:00',
                '<p>โรงพยาบาลขอแสดงความยินดีกับผู้ได้รับรางวัลบุคลากรดีเด่นประจำปี</p><p><img src="https://example.org/photo.jpg"></p><script>alert(1)</script>'
            )),
            '*'.self::BIDS.'/winning-bid' => Http::response($this->article(
                'ประกาศผู้ชนะการเสนอราคาซื้อวัสดุทันตกรรม', '2026-09-18T02:00:00+00:00',
                '<p><a href="https://drive.google.com/file/d/XYZ/view">ประกาศผู้ชนะการเสนอราคาซื้อวัสดุทันตกรรม</a></p>'
            )),
        ]);
    }

    public function test_announcements_come_in_with_their_day_their_type_their_words_and_their_documents(): void
    {
        $this->oldSite();

        $this->artisan('old-site:import')->expectsOutputToContain('brought in')->assertSuccessful();

        $this->assertSame(3, Announcement::count());

        $job = Announcement::where('title', 'like', 'เปิดรับสมัคร%')->firstOrFail();
        $this->assertSame('jobs', $job->type->slug);
        $this->assertSame('2026-09-08', $job->starts_at->toDateString());
        // Seven in the morning in Greenwich is two in the afternoon in Bangkok.
        $this->assertSame('14:07', $job->starts_at->format('H:i'));
        $this->assertTrue($job->isPublished());
        $this->assertSame([['label' => 'รายละเอียดการรับสมัคร', 'url' => 'https://drive.google.com/file/d/ABC/view?usp=sharing']], $job->links);
        // The words only named the document: there is no body to show.
        $this->assertNull($job->body);

        $news = Announcement::where('title', 'ขอแสดงความยินดีกับบุคลากรดีเด่น')->firstOrFail();
        $this->assertSame('news', $news->type->slug);
        $this->assertSame('<p>โรงพยาบาลขอแสดงความยินดีกับผู้ได้รับรางวัลบุคลากรดีเด่นประจำปี</p>', $news->body);
        // No picture came with it, and nothing that could run.
        $this->assertNull($news->image_path);
        $this->assertStringNotContainsString('alert', (string) $news->body);
        $this->assertStringNotContainsString('example.org', json_encode(Announcement::all()->toArray()));

        $this->assertSame('procurement', Announcement::where('title', 'like', 'ประกาศผู้ชนะ%')->firstOrFail()->type->slug);

        // They read on the site like any other.
        $this->get(route('announcements.show', $job))->assertOk()->assertSee('รายละเอียดการรับสมัคร')->assertSee('drive.google.com');
    }

    public function test_running_it_again_brings_nothing_in_twice_and_it_can_be_undone(): void
    {
        $this->oldSite();
        $own = Announcement::factory()->create(['title' => 'Written here']);

        $this->artisan('old-site:import')->assertSuccessful();
        $this->artisan('old-site:import')->expectsOutputToContain('already here')->assertSuccessful();

        $this->assertSame(4, Announcement::count());

        $this->artisan('old-site:import', ['--undo' => true])->assertSuccessful();

        $this->assertSame(['Written here'], Announcement::pluck('title')->all());
        $this->assertTrue($own->fresh()->exists);
    }

    public function test_a_dry_run_says_what_it_would_do_and_does_nothing(): void
    {
        $this->oldSite();

        $this->artisan('old-site:import', ['--dry-run' => true, '--pages' => 1])->expectsOutputToContain('dry run')->assertSuccessful();

        $this->assertSame(0, Announcement::count());
        $this->assertFileDoesNotExist($this->file);
    }
}
