<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\SiteSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * "Closed for maintenance", switched on from the panel (System > Site settings).
 *
 * Visitors get a short page saying so, with the hospital's phone numbers still
 * on it. Administrators carry on as usual, so they can check their work before
 * opening again, and the way in stays open: the panel and its sign-in page.
 *
 * Laravel's own `artisan down` closes the panel too, which would leave no
 * button to open the site again; this one is for the people who run it.
 */
class MaintenanceMode
{
    /** What stays reachable so that staff can sign in and an administrator can switch it off. */
    private const OPEN = ['admin', 'admin/*', 'livewire/*', 'language/*', 'up'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! SiteSettings::get('maintenance.enabled') || $this->mayPass($request)) {
            return $next($request);
        }

        // 503 with Retry-After tells search engines to come back rather than drop the pages.
        return response()->view('maintenance', ['message' => SiteSettings::get('maintenance.message')], 503, ['Retry-After' => 3600]);
    }

    private function mayPass(Request $request): bool
    {
        $user = $request->user();

        return ($user instanceof User && $user->isAdmin()) || $request->is(...self::OPEN);
    }
}
