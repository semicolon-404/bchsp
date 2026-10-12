<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\View\View;

/** The hospital's units as visitors find them: all of them, and one. */
class DepartmentController extends Controller
{
    /** How many posters, and how many announcements without a picture, a unit's board holds. */
    private const ON_BOARD = 8;

    public function index(): View
    {
        return view('departments.index', ['departments' => Department::shown()->get()]);
    }

    public function show(Department $department): View
    {
        // An administrator can open one that is switched off, to see it before showing it.
        abort_unless($department->is_active || auth()->user()?->isAdmin(), 404);

        // Its board: what it has put out that is showing now, the ones with a picture as posters.
        $announcements = $department->announcements()->current()->with('type')->inListOrder()->limit(self::ON_BOARD * 2)->get();

        return view('departments.show', [
            'department' => $department,
            // More than the board holds, or some that have ended: the whole list is a press away.
            'inAll' => $department->announcements()->published()->count(),
            'posters' => $announcements->filter(fn ($announcement) => $announcement->image_path)->take(self::ON_BOARD),
            'notices' => $announcements->reject(fn ($announcement) => $announcement->image_path)->take(self::ON_BOARD),
        ]);
    }
}
