<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementType;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The announcements as visitors read them: the list, which is also the archive,
 * narrowed by type, by fiscal year and by a word in the title; and one announcement with its files.
 */
class AnnouncementController extends Controller
{
    private const PER_PAGE = 15;

    public function index(Request $request): View
    {
        $type = is_string($request->query('type'))
            ? AnnouncementType::where('slug', $request->query('type'))->first()
            : null;

        $years = Announcement::fiscalYears($type?->id);
        $year = is_numeric($request->query('year')) && isset($years[(int) $request->query('year')]) ? (int) $request->query('year') : null;
        $term = is_string($request->query('q')) ? mb_substr(trim($request->query('q')), 0, 80) : '';

        return view('announcements.index', [
            'type' => $type,
            'types' => AnnouncementType::orderBy('sort_order')->orderBy('name')->get(),
            'year' => $year,
            'years' => $years,
            'term' => $term,
            'announcements' => Announcement::published()
                ->with('type')
                ->when($type, fn ($query) => $query->where('announcement_type_id', $type->id))
                ->when($year, fn ($query) => $query->ofFiscalYear($year))
                // The characters that mean "anything" to LIKE are looked for as themselves (see SearchController).
                ->when(mb_strlen($term) >= 2, fn ($query) => $query->where(fn ($query) => $query
                    ->whereRaw("title like ? escape '!'", ['%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%'])
                    ->orWhereRaw("title_en like ? escape '!'", ['%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%'])))
                ->inListOrder()
                ->paginate(self::PER_PAGE)
                ->withQueryString(),
        ]);
    }

    public function show(Announcement $announcement): View
    {
        // Staff who are signed in can read one before its time, to check it.
        abort_unless($announcement->isPublished() || auth()->check(), 404);

        return view('announcements.show', ['announcement' => $announcement->load('type')]);
    }
}
