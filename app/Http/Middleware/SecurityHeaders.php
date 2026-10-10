<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Instructions to the visitor's browser that close whole classes of attack,
 * sent with every response. Each is one line; none changes how the site looks.
 *
 * - X-Frame-Options / frame-ancestors: no other site may show ours inside a
 *   frame. That is how "clickjacking" works: an invisible copy of the panel
 *   under a fake button, so a signed-in member of staff's click lands on Delete.
 * - X-Content-Type-Options: an uploaded file is what the server says it is.
 *   The browser must not guess that a "picture" is really a script.
 * - Referrer-Policy: other sites learn that a visitor came from here, not
 *   which page.
 * - Permissions-Policy: nothing on the site, nor anything embedded in it, may
 *   ask for the camera, microphone, location or payment. The site uses none.
 * - base-uri, form-action, object-src: if someone did manage to plant markup
 *   in a page, it cannot repoint the page's links, send a form to another
 *   site, or load a plugin.
 * - Strict-Transport-Security, over HTTPS only: the browser refuses plain
 *   HTTP for a year, so a hostile network cannot downgrade the connection.
 *
 * Not here: a full Content-Security-Policy for scripts. Alpine and Livewire
 * evaluate expressions written in the page, which such a policy forbids; it
 * would need their CSP-safe builds and a rewrite of the inline expressions.
 */
class SecurityHeaders
{
    private const HEADERS = [
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
        'Content-Security-Policy' => "frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'",
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach (self::HEADERS as $name => $value) {
            // A response that set its own (a download, say) keeps it.
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        if ($request->secure() && ! $response->headers->has('Strict-Transport-Security')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }
}
