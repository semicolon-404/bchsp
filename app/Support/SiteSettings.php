<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * The settings an administrator can change from the panel without touching code
 * ("Site settings", App\Filament\Pages\SiteSettings).
 *
 * Some of them stand in for a config value. The rest of the code keeps reading
 * config('...') as before: when the site starts, apply() lays the saved values
 * over the config, so nothing else has to know where a value came from. A
 * setting nobody has changed has no row and keeps the value from the config
 * file (or its .env variable).
 *
 * To make another config value editable: add it to CONFIG here and a field for
 * it on the page.
 */
class SiteSettings
{
    private const CACHE_KEY = 'site-settings';

    /** Setting => how to read it back from storage. Each one overrides the config value of the same name. */
    public const CONFIG = [
        // The hospital's name, wherever the site names itself.
        'app.name' => 'string',
        'bchsp.security.staff_two_step' => 'bool',
    ];

    /** Settings with no config value behind them: read with SiteSettings::get(). */
    public const CONTENT = [
        // Closed to visitors while something is being worked on (App\Http\Middleware\MaintenanceMode).
        'maintenance.enabled', 'maintenance.message',
        // The hospital's logo: a path on the public disk, or nothing.
        'site.logo',
        'contact.address', 'contact.phone', 'contact.emergency_phone', 'contact.email',
        'social.facebook', 'social.line', 'social.youtube',
    ];

    /** The networks linked from the footer, in order, with the name shown for each. */
    public const SOCIAL = [
        'social.facebook' => 'Facebook', 'social.line' => 'LINE', 'social.youtube' => 'YouTube',
    ];

    /**
     * Everything that has been saved, as [key => value].
     *
     * Quietly empty when the table cannot be read (before the first migration, or with
     * the database down): the site then simply runs on its config files.
     *
     * @return array<string, mixed>
     */
    public static function saved(): array
    {
        try {
            return Cache::memo()->rememberForever(self::CACHE_KEY, fn () => DB::table('settings')->pluck('value', 'key')
                ->map(fn (?string $value) => $value === null ? null : json_decode($value, true))
                ->all());
        } catch (Throwable) {
            return [];
        }
    }

    /** Lay the saved values over the config. Called once as the site starts (AppServiceProvider). */
    public static function apply(): void
    {
        foreach (self::saved() as $key => $value) {
            if (isset(self::CONFIG[$key]) && $value !== null && $value !== '') {
                config([$key => match (self::CONFIG[$key]) {
                    'bool' => (bool) $value,
                    'int' => (int) $value,
                    default => (string) $value,
                }]);
            }
        }
    }

    /** A content setting ("social.facebook"), or a config-backed one as it stands right now. */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (isset(self::CONFIG[$key])) {
            return config($key, $default);
        }

        $value = self::saved()[$key] ?? null;

        return $value === null || $value === '' ? $default : $value;
    }

    /** The address of the logo an administrator uploaded, or null when there is none. */
    public static function logoUrl(): ?string
    {
        $path = self::get('site.logo');

        return is_string($path) ? Storage::disk('public')->url($path) : null;
    }

    /**
     * The footer's social links that have an address: [name => url].
     *
     * @return array<string, string>
     */
    public static function socialLinks(): array
    {
        $links = [];

        foreach (self::SOCIAL as $key => $name) {
            if ($url = self::get($key)) {
                $links[$name] = $url;
            }
        }

        return $links;
    }

    /**
     * Save what the settings page sent. Keys that are not settings are ignored.
     *
     * @param  array<string, mixed>  $values
     */
    public static function save(array $values): void
    {
        $now = now();

        foreach ($values as $key => $value) {
            if (! isset(self::CONFIG[$key]) && ! in_array($key, self::CONTENT, true)) {
                continue;
            }

            DB::table('settings')->updateOrInsert(['key' => $key], ['value' => json_encode($value), 'updated_at' => $now, 'created_at' => $now]);
        }

        Cache::memo()->forget(self::CACHE_KEY);
        self::apply();
    }
}
