<?php

namespace App\Filament\Concerns;

/**
 * For a panel resource or page that authors must not reach: staff accounts,
 * site settings, system status. Hides the menu entry and refuses the address.
 */
trait AdminOnly
{
    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }
}
