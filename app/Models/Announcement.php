<?php

namespace App\Models;

use App\Models\Concerns\InTwoLanguages;
use App\Support\PostNotices;
use App\Support\RichText;
use App\Support\Slug;
use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

/**
 * Something the hospital has to tell the public, at /announcements/{slug}:
 * a notice, a procurement result, a job opening, a poster, a piece of health
 * knowledge, the photographs of an activity. It is the one thing staff post:
 * a title, and whichever of a picture, a text, PDF files and an album it needs.
 * It can be pinned to the top of the list. It has two dates: one prepared ahead comes out
 * by itself on the first; after the second it leaves the home page and the top
 * of the list, but stays in the archive, where it is found by its fiscal year.
 * (A procurement notice has to stay readable long after it stopped being news.)
 */
class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    // Deleted goes to the bin first (deleted_at): brought back within thirty days, then gone for good (emptyBin).
    use HasFactory, InTwoLanguages, SoftDeletes;

    /** Out for the public to read, once its first date has come. */
    public const STATUS_PUBLISHED = 'published';

    /** Still being written: only staff see it. */
    public const STATUS_DRAFT = 'draft';

    /** Written by the account of a department, and waiting for someone who posts for the whole hospital to approve it. */
    public const STATUS_PENDING = 'pending';

    /** Looked at and sent back to the department for changes, with a note saying what (review_note). */
    public const STATUS_RETURNED = 'returned';

    /** How many files one announcement can carry. */
    public const MAX_FILES = 10;

    /** How many photographs an album can hold. */
    public const MAX_PHOTOS = 40;

    /** How many slides the home page turns through: more than this and nobody reaches the last one. */
    public const SLIDES = 8;

    protected $fillable = [
        'announcement_type_id', 'author_id', 'department_id', 'status', 'review_note', 'pinned_in_department',
        'title', 'title_en', 'slug', 'body', 'body_en',
        'image_path', 'in_slider', 'is_popup', 'gallery', 'attachments', 'attachment_names', 'links', 'is_pinned', 'starts_at', 'ends_at', 'event_on', 'event_until',
    ];

    protected $attributes = ['status' => self::STATUS_PUBLISHED, 'is_pinned' => false, 'pinned_in_department' => false, 'in_slider' => false, 'is_popup' => false];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'attachment_names' => 'array',
            'links' => 'array',
            'is_pinned' => 'boolean',
            'pinned_in_department' => 'boolean',
            'in_slider' => 'boolean',
            'is_popup' => 'boolean',
            'gallery' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'event_on' => 'date',
            'event_until' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Announcement $announcement) {
            $announcement->slug = Slug::unique($announcement, $announcement->slug ?: $announcement->title, 'announcement');
            $announcement->starts_at ??= now();

            // Pinned to a department's page only while it belongs to one.
            if (! $announcement->department_id) {
                $announcement->pinned_in_department = false;
            }
        });

        // One at a time on each department's page: pinning this one lets go of the one before.
        static::saved(function (Announcement $announcement) {
            if ($announcement->pinned_in_department && $announcement->department_id) {
                static::where('department_id', $announcement->department_id)->whereKeyNot($announcement->id)->where('pinned_in_department', true)->update(['pinned_in_department' => false]);
            }
        });

        // The bell in the panel rings for whoever should know where a post of a department now stands.
        static::created(fn (Announcement $announcement) => PostNotices::tell($announcement, null));

        static::updated(function (Announcement $announcement) {
            // Given another day to end on, its author may be told again when that day is near. Straight on the
            // table: the copy in hand may not know it was told.
            if ($announcement->wasChanged('ends_at')) {
                static::withTrashed()->whereKey($announcement->id)->toBase()->update(['ending_told_at' => null]);
            }

            if ($announcement->wasChanged('status')) {
                PostNotices::tell($announcement, $announcement->getRawOriginal('status'));
            } elseif ($announcement->status === self::STATUS_PUBLISHED && array_diff(array_keys($announcement->getChanges()), ['updated_at', 'ending_told_at', 'views', 'helpful']) !== []) {
                PostNotices::tellOfDepartmentChange($announcement, 'updated');
            }

            // The files belong to the announcement alone: one taken off it, or the whole announcement deleted, removes them.
            if ($announcement->wasChanged('attachments')) {
                $before = json_decode((string) $announcement->getRawOriginal('attachments'), true) ?: [];

                Storage::disk('public')->delete(array_values(array_diff($before, $announcement->attachments ?? [])));
            }

            // So do the photographs taken out of its album.
            if ($announcement->wasChanged('gallery')) {
                $before = json_decode((string) $announcement->getRawOriginal('gallery'), true) ?: [];

                Storage::disk('public')->delete(array_values(array_diff($before, $announcement->gallery ?? [])));
            }

            // So does its picture.
            if ($announcement->wasChanged('image_path') && $announcement->getOriginal('image_path')) {
                Storage::disk('public')->delete($announcement->getOriginal('image_path'));
            }
        });

        // Not an arrow function: delete() answers false for a file already gone, and a listener that answers false
        // stops the ones after it (the access log's among them).
        // In the bin its files are kept, so that it comes back whole; they go when it goes for good.
        static::deleted(fn (Announcement $announcement) => PostNotices::tellOfDepartmentChange($announcement, 'deleted'));

        static::forceDeleted(function (Announcement $announcement) {
            Storage::disk('public')->delete(array_filter([...($announcement->attachments ?? []), ...($announcement->gallery ?? []), $announcement->image_path]));
        });
    }

    /** Takes out of the bin, for good and with their files, the announcements deleted more than thirty days ago. */
    public static function emptyBin(int $days = 30): int
    {
        $old = static::onlyTrashed()->where('deleted_at', '<', now()->subDays($days))->get();
        $old->each->forceDelete();

        return $old->count();
    }

    /** What the look at its links to files kept elsewhere found (App\Support\LinkChecks). */
    public function linkChecks(): HasMany
    {
        return $this->hasMany(LinkCheck::class);
    }

    /** Whether one of its links was found not to open: the file is gone, or is not shared with everyone. */
    public function brokenLinks(): int
    {
        return $this->linkChecks->whereIn('state', [LinkCheck::DEAD, LinkCheck::PRIVATE])->count();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(AnnouncementType::class, 'announcement_type_id');
    }

    /** What the staff wrote to each other under it in the panel, oldest first. */
    public function comments(): HasMany
    {
        return $this->hasMany(AnnouncementComment::class)->oldest();
    }

    /** The unit of the hospital that put it out, when one did: it is then on that unit's board. */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** Out: published, and its first date has come. Everything the public can read, the archive included. */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', self::STATUS_PUBLISHED)->where('starts_at', '<=', now());
    }

    /**
     * Whether what this account writes waits for approval before it is out: the account of a department,
     * while the hospital asks for that (Site settings > Security).
     */
    public static function waitsForApproval(?User $user): bool
    {
        return (bool) $user?->postsForOneDepartment() && (bool) config('bchsp.security.department_approval');
    }

    /** Of a fiscal year, as written in Thailand (2569): from 1 October of the year before to 30 September. */
    public function scopeOfFiscalYear(Builder $query, int $year): void
    {
        [$from, $until] = self::fiscalYearDates($year);

        $query->where('starts_at', '>=', $from)->where('starts_at', '<', $until);
    }

    /**
     * The first moment of a fiscal year and the first moment of the next.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function fiscalYearDates(int $year): array
    {
        $from = Carbon::create($year - 543 - 1, 10, 1, 0, 0, 0, config('app.timezone'));

        return [$from, $from->copy()->addYear()];
    }

    /** The fiscal year a date falls in, as written in Thailand: October 2025 is in 2569. */
    public static function fiscalYearOf(Carbon $date): int
    {
        return $date->year + 543 + ($date->month >= 10 ? 1 : 0);
    }

    /**
     * The fiscal years that have something out, newest first, with how many announcements each holds.
     * Counted by the database in one grouped query (not every date brought over to be counted here, which grows with
     * the years); MariaDB and the tests' SQLite each take the year and month from a date in their own words.
     *
     * @return array<int, int> [2569 => 48, 2568 => 112]
     */
    public static function fiscalYears(?int $typeId = null): array
    {
        [$year, $month] = DB::connection()->getDriverName() === 'sqlite'
            ? ["cast(strftime('%Y', starts_at) as integer)", "cast(strftime('%m', starts_at) as integer)"]
            : ['year(starts_at)', 'month(starts_at)'];

        return static::published()
            ->when($typeId, fn (Builder $query) => $query->where('announcement_type_id', $typeId))
            ->toBase()
            ->selectRaw("$year + 543 + (case when $month >= 10 then 1 else 0 end) as fiscal_year, count(*) as held")
            ->groupBy('fiscal_year')
            ->orderByDesc('fiscal_year')
            ->pluck('held', 'fiscal_year')
            ->mapWithKeys(fn ($held, $fiscalYear) => [(int) $fiscalYear => (int) $held])
            ->all();
    }

    /** Inside its dates right now: what the home page shows. */
    public function scopeCurrent(Builder $query): void
    {
        $query
            ->where('status', self::STATUS_PUBLISHED)
            ->where('starts_at', '<=', now())
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()));
    }

    /** The order of every list: pinned ones that have not ended first, then the newest. */
    public function scopeInListOrder(Builder $query): void
    {
        $query
            ->orderByRaw('case when is_pinned = 1 and (ends_at is null or ends_at > ?) then 0 else 1 end', [now()])
            ->orderByDesc('starts_at')
            ->orderByDesc('id');
    }

    /** Out, whether or not it has ended. */
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED && (bool) $this->starts_at?->isPast();
    }

    /**
     * What is on the calendar in a month, day by day: the announcements that were given the day of what they
     * are about and are out. One that runs over several days is listed on the day it begins.
     *
     * @return \Illuminate\Support\Collection<string, Collection<int, Announcement>> [2026-11-03 => [...]]
     */
    public static function calendarOf(Carbon $month): \Illuminate\Support\Collection
    {
        return static::published()->with('type', 'department')
            ->whereDate('event_on', '>=', $month->copy()->startOfMonth()->toDateString())
            ->whereDate('event_on', '<=', $month->copy()->endOfMonth()->toDateString())
            ->orderBy('event_on')->orderBy('id')->get()
            ->groupBy(fn (Announcement $one) => $one->event_on->toDateString());
    }

    /** Whether anything at all is on the calendar: for the menu, which lists the calendar only then. */
    public static function hasEvents(): bool
    {
        // Asked by the home page and the menus both: worked out once a request.
        $attributes = request()->attributes;

        if (! $attributes->has('announcements.has-events')) {
            $attributes->set('announcements.has-events', static::published()->whereNotNull('event_on')->exists());
        }

        return $attributes->get('announcements.has-events');
    }

    /** How many days one that has just come out is marked as new. */
    public const NEW_FOR_DAYS = 7;

    /** Out within the last few days: marked "New" in every list. */
    public function isNew(): bool
    {
        return $this->isPublished() && $this->starts_at->gte(now()->subDays(self::NEW_FOR_DAYS));
    }

    /**
     * What has been opened most among those that came out in the last months: for "Most read" beside the list.
     *
     * @return Collection<int, Announcement>
     */
    public static function mostRead(int $limit = 5, int $months = 6): Collection
    {
        return static::published()->with('type')->where('starts_at', '>=', now()->subMonthsNoOverflow($months))->where('views', '>', 0)->orderByDesc('views')->orderByDesc('starts_at')->limit($limit)->get();
    }

    /** Still pinned to the top: pinned, and not ended. */
    public function isPinnedNow(): bool
    {
        return $this->is_pinned && ! $this->ends_at?->isPast();
    }

    public function isCurrent(): bool
    {
        return $this->isPublished() && ! $this->ends_at?->isPast();
    }

    /** What the panel's list says about it. */
    public function state(): string
    {
        return match (true) {
            $this->status === self::STATUS_DRAFT => 'Draft',
            $this->status === self::STATUS_PENDING => 'Waiting for approval',
            $this->status === self::STATUS_RETURNED => 'Sent back',
            (bool) $this->starts_at?->isFuture() => 'Scheduled',
            (bool) $this->ends_at?->isPast() => 'In the archive',
            default => 'Showing',
        };
    }

    /**
     * The slides of the home page: the pictures of the announcements that asked to be among them. Each leads to
     * its announcement, and goes when the announcement ends.
     *
     * @return Collection<int, Announcement>
     */
    public static function slides(): Collection
    {
        return static::current()->with('type')->where('in_slider', true)->whereNotNull('image_path')->inListOrder()->limit(self::SLIDES)->get();
    }

    /** The first of the posters that open over the page. */
    public static function popup(): ?Announcement
    {
        return static::popups()->first();
    }

    /**
     * The posters that open over the home page, in one window, swiped through: up to five, in the list's order.
     *
     * @return Collection<int, Announcement>
     */
    public static function popups(): Collection
    {
        return static::current()->with('type')->where('is_popup', true)->whereNotNull('image_path')->inListOrder()->limit(5)->get();
    }

    /**
     * The addresses of the photographs in its album, in the order staff gave them.
     *
     * @return list<string>
     */
    public function photos(): array
    {
        return array_map(fn (string $path) => Storage::disk('public')->url($path), array_values(array_filter($this->gallery ?? [], 'is_string')));
    }

    /** The address of its "one page" picture, or null when it has none. */
    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    public function localTitle(): string
    {
        return $this->inLanguage('title');
    }

    public function hasBody(): bool
    {
        return filled(trim(strip_tags($this->inLanguage('body'))));
    }

    /** The text as HTML that is safe to print: Filament's renderer drops anything that is not formatting. */
    public function renderedBody(): string
    {
        return RichText::html($this->inLanguage('body'));
    }

    /**
     * The attached files that are really there, each with the name it was uploaded under.
     *
     * @return list<array{name: string, url: string, size: string}>
     */
    public function files(): array
    {
        $disk = Storage::disk('public');
        $files = [];

        foreach ($this->attachments ?? [] as $path) {
            if (is_string($path) && $disk->exists($path)) {
                $files[] = [
                    'name' => (string) ($this->attachment_names[$path] ?? basename($path)),
                    'url' => $disk->url($path),
                    'size' => Number::fileSize($disk->size($path)),
                ];
            }
        }

        return $files;
    }

    /**
     * The documents kept elsewhere (Google Drive) and linked from the announcement: only web addresses
     * over HTTPS, each with something to call it.
     *
     * @return list<array{label: string, url: string, host: string}>
     */
    public function documentLinks(): array
    {
        $links = [];

        foreach ($this->links ?? [] as $row) {
            $url = is_array($row) ? trim((string) ($row['url'] ?? '')) : '';

            if (preg_match('~^https://[^\s"\'<>]+$~', $url)) {
                $host = (string) parse_url($url, PHP_URL_HOST);

                $links[] = ['label' => trim((string) ($row['label'] ?? '')) ?: $host, 'url' => $url, 'host' => $host];
            }
        }

        return $links;
    }

    /** Whether there is anything to open: a file kept here, or a link to one kept elsewhere. */
    public function hasDocuments(): bool
    {
        return filled($this->attachments) || $this->documentLinks() !== [];
    }

    /** The date as a reader in Thailand writes it: 10 ต.ค. 2569, or 10 Oct 2026 in English. */
    public function shownOn(): string
    {
        $date = $this->starts_at ?? $this->created_at;

        return app()->getLocale() === 'th'
            ? $date->locale('th')->translatedFormat('j M ').($date->year + 543)
            : $date->locale('en')->translatedFormat('j M Y');
    }

    /** About how many minutes its text takes to read: Thai at some 500 letters a minute, at least one. */
    public function minutesToRead(): int
    {
        return max(1, (int) ceil(mb_strlen(trim(strip_tags((string) $this->inLanguage('body')))) / 500));
    }

    /** Its short address, to share: /a/123, not the title spelt out as %E0%B8... */
    public function shortUrl(): string
    {
        return route('announcements.short', $this->id);
    }

    /**
     * Its day and the hour it went up, as every list and card shows it: "4 พ.ย. 2569 · 09:30 น.".
     * Put up today, how long ago instead of the day, as a social page does: "3 ชั่วโมงที่แล้ว · 09:30 น.".
     */
    public function shownAt(): string
    {
        $date = $this->starts_at ?? $this->created_at;
        $hour = $date->format('H:i').(app()->getLocale() === 'th' ? ' น.' : '');

        if ($date->isToday() && $date->isPast()) {
            $ago = $date->diffInMinutes() < 1 ? __('Just now') : $date->locale(app()->getLocale())->diffForHumans();

            return $ago.' · '.$hour;
        }

        return $this->shownOn().' · '.$hour;
    }
}
