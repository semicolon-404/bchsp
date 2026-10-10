<?php

namespace App\Support;

use App\Models\AccessLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Livewire\Livewire;

/**
 * What the people who run the site did in the admin panel: who signed in, and
 * who created, changed or deleted what, and when. Written to the access log
 * (System > Access log) as "login", "logout", "login_failed", "admin.created",
 * "admin.updated", "admin.deleted" and "admin.settings".
 *
 * Nothing in the panel has to remember to log. Every model reports its own
 * changes; a change is kept when it was made from a screen of the panel by
 * someone signed in.
 *
 * For a change, the names of the fields that changed are kept ("password"),
 * never their values: a log must not become a second copy of a password hash
 * or a secret.
 */
class StaffLog
{
    /** Changes nobody needs a line about. */
    private const IGNORED = [AccessLog::class];

    /** Fields that change by themselves. */
    private const NOISE = ['updated_at', 'created_at', 'remember_token'];

    public static function listen(): void
    {
        Event::listen(Login::class, fn (Login $event) => AccessLog::record('login', userId: $event->user->getAuthIdentifier()));
        Event::listen(Logout::class, fn (Logout $event) => AccessLog::record('logout', userId: $event->user?->getAuthIdentifier()));
        // The name that was typed, never the password.
        Event::listen(Failed::class, fn (Failed $event) => AccessLog::record(
            'login_failed',
            userId: $event->user?->getAuthIdentifier(),
            identifier: (string) ($event->credentials['email'] ?? $event->credentials['username'] ?? ''),
        ));

        foreach (['created', 'updated', 'deleted'] as $what) {
            Event::listen("eloquent.$what: *", function (string $event, array $models) use ($what) {
                if ($models[0] instanceof Model) {
                    self::note($what, $models[0]);
                }
            });
        }
    }

    public static function note(string $what, Model $model): void
    {
        if (in_array($model::class, self::IGNORED, true) || ! self::inThePanel()) {
            return;
        }

        $fields = null;

        if ($what === 'updated') {
            $fields = array_values(array_diff(array_keys($model->getChanges()), self::NOISE));

            if ($fields === []) {
                return;
            }
        }

        AccessLog::record("admin.$what", $model, identifier: $fields ? implode(', ', $fields) : null);
    }

    /** From the Site settings page: which settings were changed (their names, not their values). */
    public static function settings(array $changed): void
    {
        if ($changed !== []) {
            AccessLog::record('admin.settings', identifier: implode(', ', $changed));
        }
    }

    /** Is this request a screen of the admin panel doing something, with someone signed in? */
    private static function inThePanel(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        $screen = Livewire::current();

        return $screen && Str::startsWith($screen::class, ['App\\Filament\\', 'Filament\\']);
    }
}
