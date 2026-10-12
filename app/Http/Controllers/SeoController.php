<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Department;
use App\Models\ItaItem;
use App\Models\SitePage;
use App\Support\SiteSettings;
use Illuminate\Http\Response;

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
    public function feed(): Response
    {
        $items = Announcement::published()->with('type')->orderByDesc('starts_at')->orderByDesc('id')->limit(30)->get();

        return response()
            ->view('seo.feed', ['items' => $items, 'name' => SiteSettings::name()])
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
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
