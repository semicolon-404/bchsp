<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\Login as FilamentLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use SensitiveParameter;

/**
 * The panel's sign-in page, taking a username as well as an email address.
 *
 * The field keeps Filament's name ("email") so that everything written around
 * it still fits; what is typed is looked up as an email when it has an "@" in
 * it, and as a username when it has not.
 *
 * Kept out of app/Filament/Pages so it is not picked up as an ordinary page.
 */
class Login extends FilamentLogin
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('Username or email'))
            ->required()
            ->maxLength(255)
            ->autocomplete('username')
            ->autofocus();
    }

    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        $typed = trim((string) $data['email']);

        return [
            str_contains($typed, '@') ? 'email' : 'username' => $typed,
            'password' => $data['password'],
        ];
    }
}
