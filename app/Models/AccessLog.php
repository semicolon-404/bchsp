<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * One line of the access log: who did what, from which address, when.
 * Written through AccessLog::record(); never edited afterwards.
 */
class AccessLog extends Model
{
    use Prunable;

    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'event', 'subject_type', 'subject_id', 'identifier', 'ip_address', 'user_agent'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Rows older than the retention period, removed by the daily `model:prune` (routes/console.php).
     * The period must never be shorter than the law requires logs to be kept.
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(max(90, (int) config('bchsp.access_log.retention_days'))));
    }

    /** Write a log line for the current request. Logging must never break the action it describes. */
    public static function record(string $event, ?Model $subject = null, ?int $userId = null, ?string $identifier = null): void
    {
        try {
            static::create([
                'user_id' => $userId ?? auth()->id(),
                'event' => $event,
                // "blog_post", "user"...: the kind of thing, in the style of a table's name.
                'subject_type' => $subject ? Str::limit(Str::snake(class_basename($subject)), 40, '') : null,
                'subject_id' => $subject?->getKey(),
                'identifier' => $identifier ? Str::limit($identifier, 250, '') : null,
                'ip_address' => request()->ip(),
                'user_agent' => Str::limit((string) request()->userAgent(), 500, '') ?: null,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
