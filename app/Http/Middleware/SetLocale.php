<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Picks the language of a public page: Thai or English.
 *
 * Thai is the site's language, and what everyone gets, search engines among
 * them, until they choose English with the language switch (remembered in a
 * cookie). The words themselves are in lang/th.json, keyed by the English
 * text in the views.
 *
 * The admin panel is not among the pages this runs on: it is in Thai for everyone.
 */
class SetLocale
{
    public const COOKIE = 'locale';

    /** @var array<string, string> code => the language's own name, for the switch. The site's own language first. */
    public const LANGUAGES = ['th' => 'ไทย', 'en' => 'English'];

    public function handle(Request $request, Closure $next): Response
    {
        $chosen = $request->cookie(self::COOKIE);

        if (is_string($chosen) && isset(self::LANGUAGES[$chosen])) {
            app()->setLocale($chosen);
        }

        return $next($request);
    }
}
