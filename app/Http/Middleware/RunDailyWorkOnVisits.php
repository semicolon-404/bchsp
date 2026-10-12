<?php

namespace App\Http\Middleware;

use App\Support\WebCron;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * On a server with no scheduler (SCHEDULE_ON_VISITS=true), the site's daily work is done after a page
 * has been sent to its visitor: the visitor waits for none of it (App\Support\WebCron).
 */
class RunDailyWorkOnVisits
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        WebCron::tick();
    }
}
