<?php

namespace App\Http\Controllers;

use App\Models\ItaItem;
use App\Models\SitePage;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The Integrity and Transparency Assessment as visitors read it: one fiscal year at a time. */
class ItaController extends Controller
{
    /** /ita shows the newest year; /ita/2568 an earlier one. ?round=q2 shows only what is due in that round. */
    public function show(Request $request, ?int $year = null): View
    {
        $round = array_key_exists((string) $request->query('round'), ItaItem::ROUNDS) ? (string) $request->query('round') : null;
        $years = ItaItem::years();

        abort_if($year !== null && ! in_array($year, $years, true), 404);

        $year ??= $years[0] ?? null;

        return view('ita', [
            'year' => $year,
            'years' => $years,
            'round' => $round,
            'items' => $year ? ItaItem::shown()->where('year', $year)->get() : collect(),
            // Pages an administrator filed under ITA: the anti-corruption plan, the ethics club.
            'pages' => SitePage::menuLinks('ita'),
        ]);
    }
}
