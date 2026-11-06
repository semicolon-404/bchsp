<?php

namespace App\Models;

use App\Enums\Role;
use App\Filament\Resources\Complaints\ComplaintResource;
use App\Support\SiteSettings;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * A complaint, a suggestion or a word of praise sent in through the site (/complaint), and what the hospital
 * did about it (panel: Hospital > Complaints, administrators only).
 *
 * The sender is given a tracking number and a six-digit code, shown once; with both they read how the matter
 * stands at /complaint/track. The code is kept only as a hash. Who the sender is (when they chose to say) is
 * kept encrypted, and what they attached is kept on the private disk, reached only through the panel.
 */
class Complaint extends Model
{
    use HasFactory;

    /** What a matter can be, in the order the form offers them. English names; shown in the reader's language. */
    public const KINDS = [
        'service' => 'Complaint about a service',
        'conduct' => 'Complaint about the conduct of staff',
        'corruption' => 'Report of corruption or misconduct',
        'suggestion' => 'Suggestion',
        'praise' => 'Praise',
        'other' => 'Anything else',
    ];

    /** Where a matter stands, in the order it moves; the last two are how it ends. */
    public const STATUSES = [
        'received' => 'Received',
        'checking' => 'Being checked',
        'working' => 'Being dealt with',
        'done' => 'Finished',
        'rejected' => 'Not taken up',
    ];

    public const CLOSED = ['done', 'rejected'];

    /** Where what was attached is kept: never the public disk. */
    public const DISK = 'local';

    public const MAX_FILES = 5;

    /** Kilobytes for one file. */
    public const MAX_FILE_KB = 10240;

    /** Letters and figures that are not taken for one another when read aloud or copied by hand. */
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    protected $fillable = [
        'code', 'pin_hash', 'kind', 'title', 'detail', 'happened_on', 'place', 'work_group_id',
        'is_anonymous', 'name', 'phone', 'email', 'line_id', 'address', 'attachments',
        'status', 'reply', 'internal_note', 'handled_by', 'closed_at',
    ];

    protected $attributes = ['status' => 'received', 'is_anonymous' => false];

    protected $hidden = ['pin_hash'];

    protected function casts(): array
    {
        return [
            'happened_on' => 'date',
            'is_anonymous' => 'boolean',
            'attachments' => 'array',
            'closed_at' => 'datetime',
            // Who sent it is unreadable in the database itself, and in a copy of it.
            'name' => 'encrypted',
            'phone' => 'encrypted',
            'email' => 'encrypted',
            'line_id' => 'encrypted',
            'address' => 'encrypted',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Complaint $complaint) {
            // The day it ended, kept from when it ended; taken away if it is opened again.
            if ($complaint->isDirty('status')) {
                $complaint->closed_at = $complaint->isClosed() ? ($complaint->closed_at ?? now()) : null;
            }
        });

        // The bell in the panel rings for the administrators when one comes in.
        static::created(function (Complaint $complaint) {
            try {
                // The administrators, and the accounts of the department whose group of work it is about.
                $departmentId = $complaint->workGroup?->department_id;
                $admins = User::where('role', Role::Admin->value)
                    ->when($departmentId, fn ($query) => $query->orWhere('department_id', $departmentId))
                    ->get()->filter(fn (User $user) => $user->isAdmin() || $user->postsForOneDepartment());

                if ($admins->isNotEmpty()) {
                    Notification::make()
                        ->title(__('A new matter was sent in'))
                        ->body(__(self::KINDS[$complaint->kind] ?? 'Anything else').': '.$complaint->title)
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->iconColor('warning')
                        ->actions([Action::make('open')->label(__('Look at it'))->url(ComplaintResource::getUrl('index', panel: 'admin'))->markAsRead()])
                        ->sendToDatabase($admins);
                }
            } catch (Throwable) {
                // A bell that cannot ring must not lose the matter itself.
            }
        });

        // What was attached belongs to the matter alone.
        static::deleted(fn (Complaint $complaint) => Storage::disk(self::DISK)->deleteDirectory('complaints/'.$complaint->id));
    }

    /**
     * What one account may open: an administrator everything; a department's account what is about one of its groups
     * of work; anyone else nothing.
     */
    public function scopeVisibleTo(Builder $query, ?User $user): void
    {
        if ($user?->isAdmin()) {
            return;
        }

        if ($user?->postsForOneDepartment()) {
            $query->whereHas('workGroup', fn (Builder $group) => $group->where('department_id', $user->department_id));

            return;
        }

        $query->whereRaw('1 = 0');
    }

    public function isVisibleTo(?User $user): bool
    {
        return static::query()->visibleTo($user)->whereKey($this->getKey())->exists();
    }

    public function workGroup(): BelongsTo
    {
        return $this->belongsTo(WorkGroup::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /**
     * A tracking number nobody has yet: "CMP-", the day as a reader in Thailand writes the year (two digits of
     * the Buddhist era, month, day), and four letters and figures. Not a count: it says nothing of how many came before.
     */
    public static function newCode(): string
    {
        do {
            $tail = '';

            for ($i = 0; $i < 4; $i++) {
                $tail .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }

            $code = 'CMP-'.substr((string) (now()->year + 543), -2).now()->format('md').'-'.$tail;
        } while (static::where('code', $code)->exists());

        return $code;
    }

    /** Six digits, given to the sender once. */
    public static function newPin(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /** A tracking number as it is looked up by, however it was typed: capitals, no spaces. */
    public static function tidyCode(string $typed): string
    {
        return strtoupper((string) preg_replace('/\s+/', '', $typed));
    }

    public function pinIs(string $pin): bool
    {
        return Hash::check($pin, $this->pin_hash);
    }

    public function isClosed(): bool
    {
        return in_array($this->status, self::CLOSED, true);
    }

    /** The day by which it should be dealt with: the number of days the hospital set, from the day it came. */
    public function dueOn(): Carbon
    {
        return $this->created_at->copy()->addDays(SiteSettings::complaintDays());
    }

    public function isOverdue(): bool
    {
        return ! $this->isClosed() && $this->dueOn()->isPast();
    }

    /**
     * What was attached: each with the name it came under and where it is kept.
     *
     * @return list<array{name: string, path: string}>
     */
    public function files(): array
    {
        return array_values(array_filter(
            array_map(fn ($file) => is_array($file) && is_string($file['path'] ?? null) ? ['name' => (string) ($file['name'] ?? basename($file['path'])), 'path' => $file['path']] : null, $this->attachments ?? []),
        ));
    }
}
