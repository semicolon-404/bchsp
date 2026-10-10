<?php

namespace App\Support;

use App\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;

/**
 * Two-step sign-in: after the password, a six-digit code from an authenticator
 * app on the person's phone (Google Authenticator, Microsoft Authenticator, 1Password...).
 *
 * It is set up in the panel (the account menu > Profile). Filament keeps the
 * secret, makes the QR code and asks for the code at sign-in.
 *
 * Locked out (lost phone, no recovery codes left)? On the server:
 * php artisan two-step:off their-username   (or their email)
 */
class TwoStep
{
    public static function isOnFor(User $user): bool
    {
        return filled($user->getAppAuthenticationSecret());
    }

    /** Whether everyone who uses the panel must set it up before doing anything else there. */
    public static function isRequiredOfStaff(): bool
    {
        return (bool) config('bchsp.security.staff_two_step');
    }

    public static function provider(): AppAuthentication
    {
        return AppAuthentication::make()->recoverable()->brandName(config('app.name'));
    }

    public static function turnOffFor(User $user): void
    {
        $user->saveAppAuthenticationSecret(null);
        $user->saveAppAuthenticationRecoveryCodes(null);
    }
}
