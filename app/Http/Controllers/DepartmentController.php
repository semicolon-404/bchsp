<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\View\View;

/** The hospital's units as visitors find them: all of them, and one. */
class DepartmentController extends Controller
{
    public function index(): View
    {
        return view('departments.index', ['departments' => Department::shown()->get()]);
    }

    public function show(Department $department): View
    {
        // An administrator can open one that is switched off, to see it before showing it.
        abort_unless($department->is_active || auth()->user()?->isAdmin(), 404);

        return view('departments.show', ['department' => $department]);
    }
}
