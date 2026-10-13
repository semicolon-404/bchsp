<?php

namespace App\Http\Controllers;

use App\Models\ClinicSession;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The hospital's units as visitors find them: all of them, and the profile of one. */
class DepartmentController extends Controller
{
    /** How many posts a unit's page shows before "See all". */
    private const ON_PAGE = 10;

    public function index(): View
    {
        // With their lines of the clinic schedule, so that each card can say whether it is open this minute.
        return view('departments.index', ['departments' => Department::shown()->with('sessions')->get()]);
    }

    public function show(Request $request, Department $department): View
    {
        // An administrator can open one that is switched off, to see it before showing it.
        abort_unless($department->is_active || auth()->user()?->isAdmin(), 404);

        $people = $department->staff();
        $tab = match (true) {
            $request->query('tab') === 'photos' => 'photos',
            $request->query('tab') === 'people' && $people !== [] => 'people',
            default => 'posts',
        };
        $photos = $department->photos();
        // Its lines of the clinic schedule, which also say whether it is open at this moment.
        $sessions = ClinicSession::shown()->where('department_id', $department->id)->get();

        return view('departments.show', [
            'department' => $department,
            'tab' => $tab,
            'people' => $people,
            'sessions' => $sessions,
            'openNow' => $sessions->contains(fn (ClinicSession $session) => $session->isOpenNow()),
            // Everything it has put out, the ended ones too: the whole list is a press away.
            'inAll' => $department->announcements()->published()->count(),
            'photoCount' => $photos->count(),
            'photos' => $tab === 'photos' ? $photos : collect(),
            // Its timeline: what is showing now, pinned first, then the newest.
            'posts' => $tab === 'posts' ? $department->announcements()->current()->with('type')->orderByDesc('pinned_in_department')->inListOrder()->limit(self::ON_PAGE)->get() : collect(),
        ]);
    }
}
