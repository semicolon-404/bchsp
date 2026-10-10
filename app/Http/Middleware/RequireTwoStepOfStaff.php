<?php

namespace App\Http\Middleware;

use App\Support\TwoStep;
use Closure;
use Filament\Auth\MultiFactor\Http\Middleware\EnsureMultiFactorAuthenticationIsEnabled;
use Illuminate\Http\Request;

/**
 * On every page of the panel. While two-step sign-in is required of staff,
 * someone without it is taken to set it up; while it is not, this does nothing.
 *
 * Filament decides "required or not" once, when the routes are built. Here it
 * can change while the site runs, so the panel is told "required" and the
 * question is asked on each request.
 */
class RequireTwoStepOfStaff extends EnsureMultiFactorAuthenticationIsEnabled
{
    public function handle(Request $request, Closure $next): mixed
    {
        return TwoStep::isRequiredOfStaff() ? parent::handle($request, $next) : $next($request);
    }
}
