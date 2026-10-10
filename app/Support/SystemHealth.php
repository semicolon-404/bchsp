<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Throwable;

/**
 * What the panel's System status page reports: whether the things that must
 * be right on the server are right. Each check is a line with a verdict
 * ("ok", "warning", "problem"), what was found, and what to do about it.
 */
class SystemHealth
{
    private const HEARTBEAT = 'system:scheduler-heartbeat';

    /** Called every minute by the scheduler (routes/console.php). If this stops, so has everything scheduled. */
    public static function beat(): void
    {
        Cache::forever(self::HEARTBEAT, now()->toIso8601String());
    }

    /** When the scheduler last ran, or null if it never has on this server. */
    public static function schedulerLastRan(): ?Carbon
    {
        $at = Cache::get(self::HEARTBEAT);

        return $at ? Carbon::parse($at) : null;
    }

    /** Scheduled work runs every minute; three missed minutes means it is not running. */
    public static function schedulerIsRunning(): bool
    {
        return (bool) self::schedulerLastRan()?->gt(now()->subMinutes(3));
    }

    /**
     * Every check, in the order the page shows them.
     *
     * @return list<array{name: string, state: string, found: string, advice: ?string}>
     */
    public static function checks(): array
    {
        $check = fn (string $name, string $state, string $found, ?string $advice = null) => compact('name', 'state', 'found') + ['advice' => $state === 'ok' ? null : $advice];

        $ran = self::schedulerLastRan();
        $backup = Backups::latest();
        $free = @disk_free_space(storage_path());
        $database = self::quietly(fn () => (bool) DB::select('select 1'), false);
        $weak = self::quietly(fn () => User::all()->filter(fn (User $user) => ! TwoStep::isOnFor($user))->count(), 0);

        return [
            $check('Database', $database ? 'ok' : 'problem', $database ? __('Answering') : __('Not answering'),
                __('The site cannot work without it. Check that the database service is running.')),

            $check('Scheduled work', self::schedulerIsRunning() ? 'ok' : 'problem',
                $ran ? __('Last ran :when', ['when' => $ran->diffForHumans()]) : __('Has never run on this server'),
                __('Backups, scheduled publishing and clean-up depend on it. The server must run "php artisan schedule:run" every minute (see docs/DEPLOY.th.md).')),

            $check('Database backup', match (true) {
                $backup === null => 'problem',
                $backup['at']->lt(now()->subHours(36)) => 'problem',
                default => 'ok',
            }, $backup ? __('Newest copy :when (:size, :count kept)', ['when' => $backup['at']->diffForHumans(), 'size' => Number::fileSize($backup['bytes']), 'count' => $backup['count']]) : __('No copy has been taken yet'),
                __('A copy is taken every night at 03:10. Run "php artisan backup:database" to see what stops it. The copies are on this server only: send them somewhere else as well.')),

            $check('Free disk space', match (true) {
                $free === false => 'warning',
                $free < 1024 ** 3 => 'problem',
                $free < 5 * 1024 ** 3 => 'warning',
                default => 'ok',
            }, $free === false ? __('Unknown') : Number::fileSize($free),
                __('Pictures and documents fill the disk. Link large documents from Google Drive instead of uploading them, and remove what is no longer needed.')),

            $check('Uploaded files', self::quietly(fn () => is_dir(public_path('storage')), false) ? 'ok' : 'problem',
                self::quietly(fn () => is_dir(public_path('storage')), false) ? Number::fileSize(self::uploadBytes()) : __('The public link to the uploads is missing'),
                __('Run "php artisan storage:link" on the server, or pictures and documents will not open.')),

            $check('Two-step sign-in', match (true) {
                ! TwoStep::isRequiredOfStaff() => 'warning',
                $weak > 0 => 'warning',
                default => 'ok',
            }, ! TwoStep::isRequiredOfStaff() ? __('Not required of staff') : ($weak > 0 ? trans_choice('{1} :count account has not set it up yet|[2,*] :count accounts have not set it up yet', $weak, ['count' => $weak]) : __('Required, and everyone has it')),
                __('Switch it on under Site settings > Security. A stolen password then is not enough to get in.')),

            $check('Debug mode', config('app.debug') ? 'problem' : 'ok', config('app.debug') ? __('On') : __('Off'),
                __('With it on, an error page shows the site\'s inner workings to anyone. Set APP_DEBUG=false in .env on the live server.')),

            $check('Secure connection', request()->secure() ? 'ok' : 'warning', request()->secure() ? 'HTTPS' : 'HTTP',
                __('Passwords typed here travel unprotected. The live site must be served over HTTPS.')),

            $check('Maintenance mode', SiteSettings::get('maintenance.enabled') ? 'warning' : 'ok', SiteSettings::get('maintenance.enabled') ? __('The site is closed to visitors') : __('The site is open'),
                __('Switch it off under Site settings when the work is done.')),
        ];
    }

    /** Bytes used by uploaded pictures and documents. Walking the folder is slow, so the answer is kept ten minutes. */
    public static function uploadBytes(): int
    {
        return Cache::remember('system:upload-bytes', now()->addMinutes(10), fn () => self::quietly(function () {
            $disk = Storage::disk('public');

            return array_sum(array_map(fn (string $file) => $disk->size($file), $disk->allFiles()));
        }, 0));
    }

    private static function quietly(callable $ask, mixed $otherwise): mixed
    {
        try {
            return $ask();
        } catch (Throwable) {
            return $otherwise;
        }
    }
}
