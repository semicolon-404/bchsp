<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\BlogPost;
use App\Models\Department;
use App\Models\SitePage;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * /search: looks for the words a visitor typed in the announcements, the
 * articles, the pages and the departments that are out.
 *
 * A plain "contains" match. Thai is written without spaces, which the
 * database's full-text index cannot split into words; "contains" finds a Thai
 * word wherever it stands. What was typed is used for this one answer and is
 * not kept anywhere.
 */
class SearchController extends Controller
{
    public const MIN = 2;

    private const MAX = 80;

    private const PER_KIND = 20;

    public function __invoke(Request $request): View
    {
        $term = is_string($request->query('q')) ? mb_substr(trim($request->query('q')), 0, self::MAX) : '';
        $results = [];

        if (mb_strlen($term) >= self::MIN) {
            // The characters that mean "anything" to LIKE are looked for as themselves. "!" marks them, said outright
            // in the query: MariaDB and SQLite (the tests) do not agree on what marks them when nothing is said.
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';
            $in = fn ($query, array $columns) => $query->where(fn ($query) => collect($columns)->each(fn ($column) => $query->orWhereRaw("$column like ? escape '!'", [$like])));

            $results = array_filter([
                'Announcements' => $in(Announcement::current(), ['title', 'title_en', 'body'])->inListOrder()->limit(self::PER_KIND)->get()
                    ->map(fn (Announcement $one) => ['title' => $one->localTitle(), 'url' => route('announcements.show', $one), 'note' => $one->shownOn()])->all(),
                'Articles' => $in(BlogPost::published(), ['title', 'title_en', 'excerpt', 'body'])->latest('published_at')->limit(self::PER_KIND)->get()
                    ->map(fn (BlogPost $one) => ['title' => $one->localTitle(), 'url' => route('blog.show', $one), 'note' => $one->publishedOn()])->all(),
                'Pages' => $in(SitePage::published(), ['title', 'title_en', 'body'])->orderBy('position')->limit(self::PER_KIND)->get()
                    ->map(fn (SitePage $one) => ['title' => $one->localTitle(), 'url' => route('pages.show', $one), 'note' => null])->all(),
                'Departments' => $in(Department::query()->where('is_active', true), ['name', 'name_en', 'description'])->orderBy('sort_order')->limit(self::PER_KIND)->get()
                    ->map(fn (Department $one) => ['title' => $one->localName(), 'url' => route('departments.show', $one), 'note' => $one->location])->all(),
            ]);
        }

        return view('search', ['term' => $term, 'results' => $results, 'count' => array_sum(array_map('count', $results))]);
    }
}
