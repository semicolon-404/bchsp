<?php

namespace App\Http\Controllers;

use App\Support\Reports;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The report for executives, outside the panel's frame: a page to print (or save as PDF), and a
 * file to open in Excel. Both are for administrators only, like the panel's Reports page they are opened from.
 */
class ReportController extends Controller
{
    public function print(Request $request): View
    {
        [$from, $until, $title] = $this->period($request);

        return view('reports.print', ['summary' => Reports::summary($from, $until), 'title' => $title]);
    }

    public function download(Request $request): StreamedResponse
    {
        [$from, $until, $title] = $this->period($request);
        $rows = Reports::rows(Reports::summary($from, $until), $title);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            // The mark that tells Excel the file is UTF-8, or Thai opens as gibberish.
            fwrite($out, "\xEF\xBB\xBF");

            foreach ($rows as $row) {
                // A cell that begins like a formula is given a leading quote: Excel must not run what an article was titled.
                fputcsv($out, array_map(fn ($cell) => is_string($cell) && preg_match('/^[=+\-@\t\r]/', $cell) ? "'".$cell : $cell, $row));
            }

            fclose($out);
        }, 'report-'.$from->format('Ymd').'-'.$until->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{0: Carbon, 1: Carbon, 2: string} */
    private function period(Request $request): array
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return Reports::period(array_filter($request->only(['period', 'year', 'month', 'quarter']), 'is_scalar'));
    }
}
