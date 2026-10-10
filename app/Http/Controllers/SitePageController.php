<?php

namespace App\Http\Controllers;

use App\Models\SitePage;
use Illuminate\View\View;

/** Pages of text written in the panel (System > Pages). */
class SitePageController extends Controller
{
    public function show(SitePage $page): View
    {
        // An administrator can open a page that is not out yet, to see it before publishing.
        abort_unless($page->is_published || auth()->user()?->isAdmin(), 404);

        return view('page', ['page' => $page]);
    }
}
