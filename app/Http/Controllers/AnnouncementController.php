<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementType;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The announcements as visitors read them: the list, and one announcement with its files. */
class AnnouncementController extends Controller
{
    private const PER_PAGE = 15;

    public function index(Request $request): View
    {
        $type = is_string($request->query('type'))
            ? AnnouncementType::where('slug', $request->query('type'))->first()
            : null;

        return view('announcements.index', [
            'type' => $type,
            'types' => AnnouncementType::orderBy('sort_order')->orderBy('name')->get(),
            'announcements' => Announcement::current()
                ->with('type')
                ->when($type, fn ($query) => $query->where('announcement_type_id', $type->id))
                ->inListOrder()
                ->paginate(self::PER_PAGE)
                ->withQueryString(),
        ]);
    }

    public function show(Announcement $announcement): View
    {
        // Staff who are signed in can read one before its time, or after it, to check it.
        abort_unless($announcement->isCurrent() || auth()->check(), 404);

        return view('announcements.show', ['announcement' => $announcement->load('type')]);
    }
}
