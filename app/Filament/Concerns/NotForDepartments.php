<?php

namespace App\Filament\Concerns;

/**
 * For a panel resource that belongs to the whole hospital (ITA, downloads, the clinic schedule):
 * the account of one department does not reach it. Hides the menu entry and refuses the address.
 */
trait NotForDepartments
{
    public static function canAccess(): bool
    {
        return auth()->check() && ! auth()->user()->postsForOneDepartment();
    }
}
