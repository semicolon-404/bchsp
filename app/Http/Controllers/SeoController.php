<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Department;
use App\Models\ItaItem;
use App\Models\SitePage;
use App\Support\SiteSettings;
use App\Support\Theme;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Machine-readable files for search engines. */
class SeoController extends Controller
{
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            // Of no use to the public (resources/views/staff.blade.php), and the language switch is not a page.
            'Disallow: /staff',
            'Disallow: /language/',
            'Disallow: /search',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /**
     * The newest announcements as a feed (RSS 2.0), for a reader's news program or another site that wants
     * to show the hospital's news as it comes out. Titles and the start of each text; never a draft.
     */
    public function feed(Request $request): Response
    {
        // ?department=...: what one department has posted, for someone who follows that one.
        $department = is_string($request->query('department')) ? Department::shown()->where('slug', $request->query('department'))->first() : null;
        $items = Announcement::published()->with('type')
            ->when($department, fn ($query) => $query->where('department_id', $department->id))
            ->orderByDesc('starts_at')->orderByDesc('id')->limit(30)->get();

        return response()
            ->view('seo.feed', ['items' => $items, 'name' => SiteSettings::name().($department ? ': '.$department->localName() : '')])
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }

    /**
     * What a phone needs to keep the site on its home screen like an application: its name, its colours and its
     * picture. The address it opens at is the home page.
     */
    public function manifest(): Response
    {
        return response(json_encode([
            'name' => SiteSettings::name(),
            'short_name' => Str::limit(SiteSettings::name(), 24, ''),
            'description' => __('News, announcements and services of :name.', ['name' => SiteSettings::name()]),
            'lang' => app()->getLocale(),
            'start_url' => route('home'),
            'scope' => url('/').'/',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => Theme::accent(),
            'icons' => array_map(fn (int $size) => ['src' => route('icon', ['size' => $size]), 'sizes' => "{$size}x{$size}", 'type' => 'image/png', 'purpose' => 'any'], self::ICON_SIZES),
            'shortcuts' => [
                ['name' => __('Announcements'), 'url' => route('announcements.index')],
                ['name' => __('Clinic schedule'), 'url' => route('schedule')],
                ['name' => __('Phone numbers'), 'url' => route('phones')],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 200, ['Content-Type' => 'application/manifest+json; charset=UTF-8']);
    }

    /** The sizes the picture for a home screen is offered in. */
    private const ICON_SIZES = [192, 512];

    /**
     * The picture a phone puts on its home screen: the hospital's logo on white, with room around it; without a
     * logo, a white cross on the colour of the site. Drawn when asked for and kept by the browser for a day.
     */
    public function icon(int $size): Response
    {
        abort_unless(in_array($size, [...self::ICON_SIZES, 180], true) && function_exists('imagecreatetruecolor'), 404);

        $canvas = imagecreatetruecolor($size, $size);
        $logo = SiteSettings::get('site.logo');
        $picture = is_string($logo) && Storage::disk('public')->exists($logo) ? @imagecreatefromstring((string) Storage::disk('public')->get($logo)) : false;

        if ($picture) {
            imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));

            // Inside the middle four fifths: a phone may cut the corners of the picture round.
            $room = (int) ($size * 0.72);
            $scale = min($room / imagesx($picture), $room / imagesy($picture));
            [$width, $height] = [(int) (imagesx($picture) * $scale), (int) (imagesy($picture) * $scale)];

            imagecopyresampled($canvas, $picture, (int) (($size - $width) / 2), (int) (($size - $height) / 2), 0, 0, $width, $height, imagesx($picture), imagesy($picture));
        } else {
            [$red, $green, $blue] = sscanf(Theme::accent(), '#%02x%02x%02x');
            imagefill($canvas, 0, 0, imagecolorallocate($canvas, $red, $green, $blue));

            $white = imagecolorallocate($canvas, 255, 255, 255);
            [$arm, $reach] = [(int) ($size * 0.09), (int) ($size * 0.26)];
            $middle = (int) ($size / 2);

            imagefilledrectangle($canvas, $middle - $arm, $middle - $reach, $middle + $arm, $middle + $reach, $white);
            imagefilledrectangle($canvas, $middle - $reach, $middle - $arm, $middle + $reach, $middle + $arm, $white);
        }

        ob_start();
        imagepng($canvas);

        return response((string) ob_get_clean(), 200, ['Content-Type' => 'image/png', 'Cache-Control' => 'public, max-age=86400']);
    }

    /**
     * The service worker: the small program a browser keeps for the site. It does one thing: when a page is
     * asked for and there is no connection, it answers with the page of phone numbers it kept (/offline).
     * It keeps nothing else, so a visitor never reads a stale page.
     */
    public function worker(): Response
    {
        $offline = json_encode(route('offline'), JSON_UNESCAPED_SLASHES);

        $script = <<<JS
        const OFFLINE = {$offline};
        const KEPT = 'bchsp-offline-v1';

        self.addEventListener('install', (event) => {
            event.waitUntil(caches.open(KEPT).then((cache) => cache.add(new Request(OFFLINE, { cache: 'reload' }))).then(() => self.skipWaiting()));
        });

        self.addEventListener('activate', (event) => {
            event.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((key) => key !== KEPT).map((key) => caches.delete(key)))).then(() => self.clients.claim()));
        });

        self.addEventListener('fetch', (event) => {
            if (event.request.mode !== 'navigate') return;

            event.respondWith(fetch(event.request).catch(() => caches.match(OFFLINE)));
        });
        JS;

        return response($script, 200, ['Content-Type' => 'text/javascript; charset=UTF-8', 'Cache-Control' => 'no-cache', 'Service-Worker-Allowed' => '/']);
    }

    /** Every page the public can open, with when it last changed where that is known. */
    public function sitemap(): Response
    {
        $urls = collect([
            ['loc' => route('home'), 'priority' => '1.0'],
            ['loc' => route('announcements.index'), 'priority' => '0.9'],
            ['loc' => route('departments.index'), 'priority' => '0.7'],
            ['loc' => route('contact'), 'priority' => '0.7'],
            ['loc' => route('downloads'), 'priority' => '0.5'],
            ['loc' => route('updates'), 'priority' => '0.4'],
            ['loc' => route('phones'), 'priority' => '0.6'],
            ['loc' => route('complaint'), 'priority' => '0.6'],
            ['loc' => route('complaint.track'), 'priority' => '0.4'],
            ['loc' => route('faq'), 'priority' => '0.7'],
            ['loc' => route('services'), 'priority' => '0.7'],
            ['loc' => route('calendar'), 'priority' => '0.6'],
            ['loc' => route('posts'), 'priority' => '0.7'],
        ]);

        foreach (ItaItem::years() as $index => $year) {
            $urls->push(['loc' => route('ita', $index === 0 ? [] : ['year' => $year]), 'priority' => '0.6']);
        }

        SitePage::published()->get(['slug', 'updated_at'])
            ->each(fn (SitePage $page) => $urls->push(['loc' => route('pages.show', $page), 'lastmod' => $page->updated_at?->toAtomString(), 'priority' => '0.5']));

        Department::shown()->get(['slug', 'updated_at'])
            ->each(fn (Department $department) => $urls->push(['loc' => route('departments.show', $department), 'lastmod' => $department->updated_at?->toAtomString(), 'priority' => '0.5']));

        Announcement::published()->inListOrder()->limit(5000)->get(['slug', 'updated_at'])
            ->each(fn (Announcement $announcement) => $urls->push(['loc' => route('announcements.show', $announcement), 'lastmod' => $announcement->updated_at?->toAtomString(), 'priority' => '0.6']));

        return response()
            ->view('seo.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
