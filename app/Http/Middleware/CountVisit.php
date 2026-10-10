<?php

namespace App\Http\Middleware;

use App\Support\Visits;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds one to today's count of pages opened (App\Support\Visits), for a page
 * of the public site that opened as it should. The panel, the language switch
 * and the files for search engines are not pages anyone "visits".
 */
class CountVisit
{
    private const NOT_COUNTED = ['admin', 'admin/*', 'livewire/*', 'language/*', 'robots.txt', 'sitemap.xml', 'up', 'storage/*'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET') && $response->getStatusCode() === 200 && ! $request->is(...self::NOT_COUNTED)) {
            Visits::count($request);
        }

        return $response;
    }
}
