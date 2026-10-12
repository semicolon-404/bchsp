<?php

namespace App\Support;

use App\Models\AccessLog;
use App\Models\Announcement;
use App\Models\AnnouncementType;
use App\Models\Banner;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Department;
use App\Models\Document;
use App\Models\ItaItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Sample content, to see every part of the site with something in it before the
 * real content exists: announcements over three fiscal years, slides and posters
 * upright and wide, articles with albums, ITA items, documents, three months of
 * visits and of staff work.
 *
 *   php artisan demo:fill     puts it in
 *   php artisan demo:clear    takes all of it out again, and only it
 *
 * Everything it makes is named "[ตัวอย่าง] ..." and written down in
 * storage/app/demo.json, which is how clear() knows what is its own. Real
 * content entered meanwhile is never touched.
 */
class DemoData
{
    public const MARK = '[ตัวอย่าง]';

    /** Fonts that can draw Thai, where they are usually found. Without one the pictures are labelled in English. */
    private const FONTS = [
        'C:/Windows/Fonts/LeelawUI.ttf', 'C:/Windows/Fonts/tahoma.ttf',
        '/usr/share/fonts/truetype/noto/NotoSansThai-Regular.ttf', '/usr/share/fonts/opentype/noto/NotoSansThai-Regular.otf',
        '/usr/share/fonts/truetype/tlwg/Garuda.ttf', '/System/Library/Fonts/Supplemental/Thonburi.ttc',
    ];

    /** @var array{models: array<class-string, list<int>>, files: list<string>, visits: list<array{0: string, 1: string, 2: int}>} */
    private array $made = ['models' => [], 'files' => [], 'visits' => []];

    /** Where the list of what was made is kept, when not in the usual place. The tests keep their own: clearing the site's would leave its sample content with no way out. */
    public static ?string $manifestAt = null;

    public static function manifest(): string
    {
        return self::$manifestAt ?? storage_path('app/demo.json');
    }

    public static function isFilled(): bool
    {
        return is_file(self::manifest());
    }

    /**
     * Put the sample content in.
     *
     * @return array<string, int> how many of each thing were made
     */
    public function fill(): array
    {
        // The same "random" content every time, so that two people looking at a problem see the same thing.
        mt_srand(2569);

        try {
            $this->slidesAndPosters();
            $this->announcements();
            $this->articles();
            $this->ita();
            $this->documents();
            $this->visits();
            $this->staffWork();
        } finally {
            // Written even when something failed half-way, so that clear() can take out what was made.
            File::put(self::manifest(), json_encode($this->made, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            mt_srand();
        }

        return array_map('count', $this->made['models']) + ['pictures and files' => count($this->made['files']), 'days of visits' => count(array_unique(array_column($this->made['visits'], 0)))];
    }

    /**
     * Take out everything fill() made, and nothing else.
     *
     * @return int how many records went
     */
    public static function clear(): int
    {
        $made = json_decode((string) @file_get_contents(self::manifest()), true) ?: ['models' => [], 'files' => [], 'visits' => []];
        $removed = 0;

        foreach ($made['models'] as $class => $ids) {
            // One at a time, through the model: that is what removes its pictures and files with it.
            foreach ($class::whereIn('id', $ids)->get() as $record) {
                $record->delete();
                $removed++;
            }
        }

        Storage::disk('public')->delete($made['files']);

        foreach ($made['visits'] as [$day, $section, $views]) {
            DB::table('site_visits')->where(['day' => $day, 'section' => $section])->decrement('views', $views);
        }

        DB::table('site_visits')->where('views', '<=', 0)->delete();
        File::delete(self::manifest());

        return $removed;
    }

    private function slidesAndPosters(): void
    {
        foreach ([
            ['สไลด์แนวนอน: รณรงค์ฉีดวัคซีนไข้หวัดใหญ่', 1600, 600, 'banner', [21, 128, 61], false],
            ['สไลด์แนวนอน: ตารางคลินิกพิเศษประจำเดือน', 1600, 600, 'banner', [29, 78, 216], false],
            ['สไลด์แนวตั้ง: ประกาศ one page งดบริการทันตกรรม', 1240, 1754, 'banner', [180, 83, 9], false],
            ['โปสเตอร์แนวตั้ง: 10 เคล็ดลับเลิกบุหรี่', 1240, 1754, 'poster', [109, 40, 217], true],
            ['โปสเตอร์แนวนอน: ป้องกันโรคพิษสุนัขบ้า', 1754, 1240, 'poster', [190, 24, 93], false],
            ['โปสเตอร์แนวตั้ง: ล้างมือ 7 ขั้นตอน', 1240, 1754, 'poster', [13, 148, 136], false],
            ['โปสเตอร์จัตุรัส: บริจาคโลหิต', 1400, 1400, 'poster', [185, 28, 28], false],
        ] as $order => [$title, $width, $height, $placement, $colour, $popup]) {
            $this->keep(Banner::create([
                'title' => self::MARK.' '.$title,
                'image_path' => $this->picture('banners', $title, $width, $height, $colour),
                'placement' => $placement,
                'is_popup' => $popup,
                'position' => 900 + $order,
            ]));
        }
    }

    private function announcements(): void
    {
        $types = AnnouncementType::pluck('id', 'slug');
        $boards = $this->boards();
        $months = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
        $make = function (array $attributes) {
            $record = new Announcement($attributes + ['author_id' => User::value('id')]);
            $record->views = mt_rand(3, 400);
            $record->title = self::MARK.' '.$record->title;
            $record->save();
            $this->keep($record);

            return $record;
        };

        // Thirty months back: enough to fill three fiscal years and show the archive.
        for ($back = 30; $back >= 0; $back--) {
            $month = now()->startOfMonth()->subMonthsNoOverflow($back);
            $name = $months[$month->month].' '.($month->year + 543);

            // Procurement: every month, as on the hospital's old site. Some with a PDF kept here, some linked from Google Drive.
            foreach (['ซื้อยา', 'ซื้อวัสดุทันตกรรม'] as $index => $what) {
                if ($index === 1 && $back % 2) {
                    continue;
                }

                $make([
                    'announcement_type_id' => $types['procurement'] ?? null,
                    'title' => "ประกาศผู้ชนะการเสนอราคา$what โดยวิธีเฉพาะเจาะจง ประจำเดือน$name",
                    'starts_at' => $month->copy()->addDays(mt_rand(1, 20))->setTime(mt_rand(8, 15), 0)->min(now()->subMinute()),
                    'attachments' => $back % 3 ? [$path = $this->pdf('announcements/files', "ประกาศผู้ชนะ $what $name")] : null,
                    'attachment_names' => $back % 3 ? [$path => "ประกาศผู้ชนะการเสนอราคา $what $name.pdf"] : null,
                    'links' => $back % 3 ? null : [['label' => "ประกาศผู้ชนะ $what $name (Google Drive)", 'url' => 'https://drive.google.com/file/d/EXAMPLE-'.$back.'/view']],
                ]);
            }

            // News: most months. Every third one is a "one page" picture, upright or wide in turn.
            if ($back % 4 !== 3) {
                $picture = $back % 3 === 0;
                $upright = $back % 6 === 0;
                $topic = ['รณรงค์ป้องกันโรคไข้เลือดออก', 'ขอเชิญร่วมบริจาคโลหิต', 'แจ้งปรับเวลาให้บริการคลินิกพิเศษ', 'ขอแสดงความยินดีกับบุคลากรดีเด่น', 'ประชาสัมพันธ์การตรวจสุขภาพประจำปี'][$back % 5];

                $make([
                    'announcement_type_id' => $types['news'] ?? null,
                    'title' => "$topic ประจำเดือน$name".($picture ? ($upright ? ' (ภาพแนวตั้ง)' : ' (ภาพแนวนอน)') : ''),
                    'body' => '<p>ข้อความตัวอย่างของประกาศ ใช้สำหรับดูการจัดวางหน้าเว็บ รายละเอียดจริงจะถูกใส่โดยเจ้าหน้าที่</p><ul><li>รายการที่หนึ่ง</li><li>รายการที่สอง</li></ul>',
                    'starts_at' => $month->copy()->addDays(mt_rand(2, 24))->setTime(9, 30)->min(now()->subMinute()),
                    'image_path' => $picture ? $this->picture('announcements/pictures', $topic, $upright ? 1240 : 1754, $upright ? 1754 : 1240, [[21, 128, 61], [29, 78, 216], [180, 83, 9]][$back % 3]) : null,
                    // The two newest pictures are also among the slides of the home page.
                    'in_slider' => $picture && $back <= 3,
                    'department_id' => $boards ? $boards[$back % count($boards)] : null,
                ]);
            }

            // Job openings: now and then.
            if ($back % 5 === 0) {
                $make([
                    'announcement_type_id' => $types['jobs'] ?? null,
                    'title' => "รับสมัครสอบคัดเลือกบุคคลภายนอกเพื่อปฏิบัติงาน ตำแหน่งพนักงานช่วยเหลือคนไข้ ($name)",
                    'body' => '<p>ข้อความตัวอย่าง: คุณสมบัติ วันรับสมัคร และเอกสารที่ต้องใช้</p>',
                    'starts_at' => $month->copy()->addDays(5)->setTime(10, 0)->min(now()->subMinute()),
                    'ends_at' => $month->copy()->addDays(25),
                    'attachments' => [$path = $this->pdf('announcements/files', "รายละเอียดการรับสมัคร $name")],
                    'attachment_names' => [$path => "รายละเอียดการรับสมัคร $name.pdf"],
                ]);
            }
        }

        // One of each state the list has to show.
        $make(['announcement_type_id' => $types['news'] ?? null, 'title' => 'ประกาศปักหมุด: เปิดให้บริการคลินิกนอกเวลาราชการ', 'is_pinned' => true, 'starts_at' => now()->subDays(2), 'body' => '<p>ตัวอย่างประกาศที่ปักหมุดไว้บนสุดของรายการ</p>']);
        $make(['announcement_type_id' => $types['news'] ?? null, 'title' => 'ประกาศที่ใกล้สิ้นสุด: งดให้บริการทันตกรรมวันศุกร์นี้', 'starts_at' => now()->subDays(3), 'ends_at' => now()->addDays(2)]);
        $make(['announcement_type_id' => $types['news'] ?? null, 'title' => 'ประกาศที่ตั้งเวลาไว้: จะเผยแพร่สัปดาห์หน้า', 'starts_at' => now()->addDays(7)]);
        $make(['announcement_type_id' => $types['procurement'] ?? null, 'title' => 'ประกาศชื่อยาวมากเพื่อทดสอบการตัดบรรทัด: ประกาศผลผู้ชนะการจัดซื้อยาและวัสดุเภสัชกรรม เวชภัณฑ์มิใช่ยา วัสดุวิทยาศาสตร์การแพทย์ และวัสดุทันตกรรม ประจำไตรมาสที่ 1 ปีงบประมาณ 2570 ของโรงพยาบาลบัวเชด อำเภอบัวเชด จังหวัดสุรินทร์', 'starts_at' => now()->subDays(4)]);
    }

    /**
     * The units whose boards get sample news and articles: the first few, so that each board has enough to look at.
     *
     * @return list<int>
     */
    private function boards(): array
    {
        return Department::shown()->limit(3)->pluck('id')->all();
    }

    private function articles(): void
    {
        $knowledge = BlogCategory::firstOrCreate(['name' => 'ความรู้สุขภาพ'], ['name_en' => 'Health knowledge', 'sort_order' => 1]);
        $activity = BlogCategory::firstOrCreate(['name' => 'ข่าวกิจกรรม'], ['name_en' => 'Activities', 'sort_order' => 2]);
        $boards = $this->boards();

        foreach ([
            ['กิจกรรมรับบริจาคโลหิตประจำไตรมาส', $activity, 6, 3, false],
            ['อบรมฟื้นฟูการช่วยชีวิตขั้นพื้นฐานสำหรับบุคลากร', $activity, 9, 12, false],
            ['ออกหน่วยแพทย์เคลื่อนที่ ตำบลบัวเชด', $activity, 4, 25, false],
            ['กิจกรรมวันพยาบาลแห่งชาติ', $activity, 0, 40, false],
            ['ล้างมือ 7 ขั้นตอน ป้องกันโรคติดต่อ', $knowledge, 0, 8, false],
            ['ไข้เลือดออก: อาการที่ต้องรีบมาโรงพยาบาล', $knowledge, 0, 20, false],
            ['การดูแลเท้าสำหรับผู้ป่วยเบาหวาน', $knowledge, 0, 55, false],
            ['บทความที่ยังเป็นฉบับร่าง', $knowledge, 0, 0, true],
        ] as $index => [$title, $category, $photos, $daysAgo, $draft]) {
            $post = new BlogPost([
                'author_id' => User::value('id'),
                'blog_category_id' => $category->id,
                'department_id' => $boards ? $boards[$index % count($boards)] : null,
                'title' => self::MARK.' '.$title,
                'excerpt' => 'ข้อความสรุปตัวอย่างหนึ่งถึงสองประโยค ใช้สำหรับดูการจัดวางการ์ดบทความ',
                'body' => '<h2>หัวข้อย่อยตัวอย่าง</h2><p>เนื้อหาตัวอย่างของบทความ ใช้สำหรับดูการจัดวางตัวอักษร ระยะบรรทัด และรูปภาพในหน้าอ่านบทความ เนื้อหาจริงจะเขียนโดยเจ้าหน้าที่ของโรงพยาบาล</p><ul><li>ประเด็นที่หนึ่ง</li><li>ประเด็นที่สอง</li><li>ประเด็นที่สาม</li></ul><blockquote>ข้อความเน้นตัวอย่าง</blockquote><p>ย่อหน้าปิดท้ายตัวอย่าง</p>',
                'cover_path' => $this->picture('blog/covers', $title, 1600, 900, [[21, 128, 61], [29, 78, 216], [109, 40, 217], [180, 83, 9]][$index % 4]),
                'gallery' => $photos ? array_map(fn (int $n) => $this->picture('blog/albums', "ภาพกิจกรรม $n", $n % 3 ? 1600 : 1200, $n % 3 ? 1200 : 1600, [mt_rand(20, 160), mt_rand(60, 160), mt_rand(60, 200)]), range(1, $photos)) : null,
                'status' => $draft ? BlogPost::STATUS_DRAFT : BlogPost::STATUS_PUBLISHED,
                'published_at' => $draft ? null : now()->subDays($daysAgo)->setTime(10, 0),
            ]);
            $post->views = $draft ? 0 : mt_rand(10, 600);
            $post->save();
            $this->keep($post);
        }
    }

    private function ita(): void
    {
        $thisYear = Announcement::fiscalYearOf(now());

        foreach ([$thisYear - 1 => 23, $thisYear => 23] as $year => $count) {
            for ($number = 1; $number <= $count; $number++) {
                // Last year complete; this year about half done, as it would be part-way through.
                $done = $year < $thisYear || $number % 2 === 1;

                $this->keep(ItaItem::create([
                    'year' => $year,
                    'number' => $number,
                    'code' => 'MOIT '.$number,
                    'title' => self::MARK." หัวข้อการประเมินข้อที่ $number (ใส่ชื่อหัวข้อจริงตามเกณฑ์ของปี $year)",
                    'attachments' => $done && $number % 3 === 0 ? [$path = $this->pdf('ita', "MOIT $number ปี $year")] : null,
                    'attachment_names' => $done && $number % 3 === 0 ? [$path => "MOIT $number หลักฐาน ปี $year.pdf"] : null,
                    'links' => $done && $number % 3 !== 0 ? [['label' => "หลักฐาน MOIT $number (Google Drive)", 'url' => "https://drive.google.com/file/d/EXAMPLE-ITA-$year-$number/view"]] : null,
                ]));
            }
        }
    }

    private function documents(): void
    {
        foreach ([
            ['แบบแสดงความจำนงเข้าร่วมประชุม/อบรม/สัมมนา', 'แบบฟอร์ม'],
            ['แบบขออนุญาตใช้รถส่วนกลาง', 'แบบฟอร์ม'],
            ['แบบฟอร์มขอสำเนาเวชระเบียน', 'แบบฟอร์ม'],
            ['ระเบียบการลา', 'ระเบียบและแนวปฏิบัติ'],
            ['อัตราค่าบริการสาธารณสุข', 'ระเบียบและแนวปฏิบัติ'],
            ['เอกสารที่ไม่ได้จัดหัวข้อ', null],
        ] as $order => [$title, $group]) {
            $this->keep(Document::create([
                'title' => self::MARK.' '.$title,
                'group' => $group,
                'file_path' => $this->pdf('documents', $title),
                'file_name' => $title.'.pdf',
                'sort_order' => 900 + $order,
            ]));
        }
    }

    private function visits(): void
    {
        // More on weekdays, growing slowly, most of it on the home page and the announcements: as a real site's would be.
        $shares = ['home' => 30, 'news' => 22, 'procurement' => 16, 'ita' => 6, 'articles' => 8, 'schedule' => 7, 'departments' => 2, 'pages' => 3, 'contact' => 4, 'downloads' => 1, 'search' => 1];

        for ($back = 120; $back >= 1; $back--) {
            $day = now()->subDays($back);
            $total = (int) ((120 - $back) * 0.6 + mt_rand(40, 90)) * ($day->isWeekend() ? 0.45 : 1);

            foreach ($shares as $section => $share) {
                $views = max(0, (int) round($total * $share / 100 + mt_rand(-2, 3)));

                if ($views > 0) {
                    $key = ['day' => $day->toDateString(), 'section' => $section];

                    if (DB::table('site_visits')->where($key)->increment('views', $views) === 0) {
                        DB::table('site_visits')->insert($key + ['views' => $views]);
                    }

                    $this->made['visits'][] = [$key['day'], $section, $views];
                }
            }
        }
    }

    private function staffWork(): void
    {
        // A second member of staff, so that the report of who did what has more than one line. Nobody can sign in as them.
        $author = User::create(['name' => self::MARK.' นางสาวมาลี ผู้เขียนเนื้อหา', 'username' => 'demo-'.Str::lower(Str::random(6)), 'password' => Str::random(40)]);
        $this->keep($author);

        foreach (User::all() as $user) {
            for ($back = 90; $back >= 0; $back -= mt_rand(1, 4)) {
                $at = now()->subDays($back)->setTime(mt_rand(8, 16), mt_rand(0, 59));

                foreach (array_merge(['login'], array_fill(0, mt_rand(0, 3), 'admin.created'), array_fill(0, mt_rand(0, 2), 'admin.updated'), mt_rand(0, 9) ? [] : ['admin.deleted']) as $event) {
                    $log = new AccessLog(['user_id' => $user->id, 'event' => $event, 'subject_type' => $event === 'login' ? null : 'announcement', 'ip_address' => '10.0.0.'.mt_rand(2, 250)]);
                    $log->created_at = $at;
                    $log->save();
                    $this->keep($log);
                }
            }
        }
    }

    private function keep(Model $record): void
    {
        $this->made['models'][$record::class][] = $record->getKey();
    }

    /**
     * A picture of a size and colour with what it stands for written on it, kept on the public disk.
     * The colour is muted and melts into its neighbours, like a desktop's wallpaper: easy on the eye
     * beside the site's own colours, and still plainly not a photograph.
     */
    private function picture(string $directory, string $label, int $width, int $height, array $colour): string
    {
        // Greyed and lightened, then a few patches of nearby colours on a canvas of a few dots;
        // stretching that to full size is what blends them.
        $muted = fn (array $from, float $grey) => array_map(fn (int $part, int $pale) => (int) round($part * (1 - $grey) + $pale * $grey), $from, [226, 229, 234]);
        [$red, $green, $blue] = $colour;
        $patches = [$muted([$green, $blue, $red], 0.62), $muted([$blue, $red, $green], 0.7), $muted([255, 255, 255], 0.2), $muted($colour, 0.3)];

        $small = imagecreatetruecolor(6, 6);
        imagefill($small, 0, 0, imagecolorallocate($small, ...$muted($colour, 0.5)));
        mt_srand(crc32($label));
        foreach ($patches as $patch) {
            imagesetpixel($small, mt_rand(0, 5), mt_rand(0, 5), imagecolorallocate($small, ...$patch));
            imagesetpixel($small, mt_rand(0, 5), mt_rand(0, 5), imagecolorallocate($small, ...$patch));
        }
        mt_srand();

        // In two steps: stretched in one, the six dots show as squares.
        $middle = imagescale($small, 96, 96, IMG_BILINEAR_FIXED);
        for ($pass = 0; $pass < 12; $pass++) {
            imagefilter($middle, IMG_FILTER_GAUSSIAN_BLUR);
        }
        $image = imagescale($middle, $width, $height, IMG_BILINEAR_FIXED);
        imagedestroy($small);
        imagedestroy($middle);

        // A fine line just inside the edge, so that it is plain when a picture has been cropped.
        imagerectangle($image, 8, 8, $width - 9, $height - 9, imagecolorallocatealpha($image, 255, 255, 255, 50));

        $ink = imagecolorallocate($image, 30, 41, 59);
        $size = "$width × $height";
        $font = collect(self::FONTS)->first(fn (string $path) => is_file($path));

        if ($font && function_exists('imagettftext')) {
            $points = max(18, (int) ($width / 28));

            foreach ([[self::MARK, 0.42], [Str::limit($label, 44), 0.52], [$size, 0.62]] as [$text, $at]) {
                $box = imagettfbbox($points, 0, $font, $text);
                imagettftext($image, $points, 0, (int) (($width - ($box[2] - $box[0])) / 2), (int) ($height * $at), $ink, $font, $text);
            }
        } else {
            imagestring($image, 5, 40, (int) ($height / 2), 'SAMPLE '.$size, $ink);
        }

        ob_start();
        imagewebp($image, null, 80);
        $path = $directory.'/demo-'.Str::lower(Str::random(20)).'.webp';
        Storage::disk('public')->put($path, (string) ob_get_clean(), 'public');
        imagedestroy($image);

        return $this->made['files'][] = $path;
    }

    /** A one-page PDF that says it is a sample, kept on the public disk. */
    private function pdf(string $directory, string $about): string
    {
        // Written by hand, plain ASCII: the smallest file every PDF reader opens. The name it is offered under carries the Thai.
        $text = 'SAMPLE DOCUMENT - '.preg_replace('/[^A-Za-z0-9 .-]/', '', Str::ascii($about)).' - for testing the website only';
        $stream = "BT /F1 16 Tf 60 760 Td ($text) Tj ET";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n$stream\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n$object\nendobj\n";
        }

        $start = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n".implode('', array_map(fn (int $offset) => sprintf("%010d 00000 n \n", $offset), $offsets));
        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n$start\n%%EOF\n";

        $path = $directory.'/demo-'.Str::lower(Str::random(20)).'.pdf';
        Storage::disk('public')->put($path, $pdf, 'public');

        return $this->made['files'][] = $path;
    }
}
