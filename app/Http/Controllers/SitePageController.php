<?php

namespace App\Http\Controllers;

use App\Models\SitePage;
use Illuminate\View\View;

/** Pages of text written in the panel (System > Pages). */
class SitePageController extends Controller
{
    /**
     * Pages of the "About us" group that are drawn from what the panel holds and not written as text: each
     * keeps its place in the group's menu, its title and whether it is out, and has a view of its own.
     */
    public const DRAWN = ['structure' => 'about.structure', 'executives' => 'about.executives'];

    public function show(SitePage $page): View
    {
        // An administrator can open a page that is not out yet, to see it before publishing.
        abort_unless($page->is_published || auth()->user()?->isAdmin(), 404);

        return view(self::DRAWN[$page->slug] ?? 'page', ['page' => $page]);
    }
}
