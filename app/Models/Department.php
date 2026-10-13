<?php

namespace App\Models;

use App\Models\Concerns\InTwoLanguages;
use App\Support\Slug;
use Database\Factories\DepartmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * A unit of the hospital, at /departments/{slug}: its profile. A cover and a round
 * picture, its name and a line under it, what it does, where it is, its phone
 * number and its hours, the services it gives, the people who work there, its lines
 * of the clinic schedule, and what it has posted (the announcements that name it). One that is switched off is kept in the
 * panel and hidden from the site.
 */
class Department extends Model
{
    /** @use HasFactory<DepartmentFactory> */
    use HasFactory, InTwoLanguages;

    protected $fillable = [
        'name', 'name_en', 'headline', 'headline_en', 'slug', 'description', 'description_en', 'services',
        'location', 'phone', 'email', 'hours', 'facebook_url', 'line_url', 'image_path', 'avatar_path', 'icon', 'people', 'is_active', 'sort_order',
    ];

    protected $attributes = ['is_active' => true, 'sort_order' => 0];

    /**
     * The symbols a department can stand under when it has no picture of its own (heroicons, outline).
     * English names; the panel shows them in the reader's language.
     */
    public const ICONS = [
        'heart' => 'Heart (medicine, wards)',
        'bolt' => 'Bolt (emergency)',
        'beaker' => 'Beaker (laboratory, pharmacy)',
        'face-smile' => 'Smile (dental)',
        'eye' => 'Eye',
        'hand-raised' => 'Hand (physical therapy, Thai medicine)',
        'user-group' => 'People (outpatients, family care)',
        'home-modern' => 'Building (administration)',
        'banknotes' => 'Banknotes (finance)',
        'clipboard-document-check' => 'Clipboard (quality, insurance)',
        'computer-desktop' => 'Computer (IT)',
        'shield-check' => 'Shield (prevention, public health)',
        'chat-bubble-left-right' => 'Speech (counselling)',
        'truck' => 'Vehicle (referral, transport)',
        'sparkles' => 'Sparkles (nutrition, cleaning)',
        'academic-cap' => 'Cap (training)',
    ];

    /** Its symbol, when one was chosen and still exists. */
    public function icon(): ?string
    {
        return isset(self::ICONS[$this->icon]) ? $this->icon : null;
    }

    /** How many people a department can list. */
    public const MAX_PEOPLE = 40;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'people' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Department $department) {
            $department->slug = Slug::unique($department, $department->slug ?: $department->name, 'department');
        });

        // Its pictures belong to the department alone, so each goes when the department does or when it is replaced.
        static::updated(function (Department $department) {
            foreach (['image_path', 'avatar_path'] as $picture) {
                if ($department->wasChanged($picture) && $department->getOriginal($picture)) {
                    Storage::disk('public')->delete($department->getOriginal($picture));
                }
            }

            // So do the photographs of people taken off its list, or given another.
            if ($department->wasChanged('people')) {
                $before = array_column(json_decode((string) $department->getRawOriginal('people'), true) ?: [], 'photo');

                Storage::disk('public')->delete(array_values(array_filter(array_diff($before, $department->peoplePhotos()))));
            }
        });

        static::deleted(function (Department $department) {
            Storage::disk('public')->delete(array_filter([$department->image_path, $department->avatar_path, ...$department->peoplePhotos()]));
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** What the unit has put out: the unit's board on its page. */
    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    /** Its lines of the clinic schedule (App\Models\ClinicSession). */
    public function sessions(): HasMany
    {
        return $this->hasMany(ClinicSession::class);
    }

    /** Shown on the site, in the administrator's order. */
    public function scopeShown(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    public function localName(): string
    {
        return $this->inLanguage('name');
    }

    public function localDescription(): string
    {
        return $this->inLanguage('description');
    }

    /** The line under its name: what it is, in a few words. */
    public function localHeadline(): string
    {
        return $this->inLanguage('headline');
    }

    /** The address of its cover: the wide picture across the top of its page. */
    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    /** The address of its small round picture, or null when it has none. */
    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null;
    }

    /** What stands in for the round picture: the first letter of its name, past the words every unit begins with. */
    public function initial(): string
    {
        $name = trim((string) preg_replace('/^(กลุ่มงาน|งาน|ฝ่าย|ศูนย์|แผนก|หน่วย)/u', '', $this->localName())) ?: $this->localName();

        // A Thai vowel written before its consonant is not what the name is known by.
        return mb_strtoupper(mb_substr((string) preg_replace('/^[เแโใไ]+/u', '', $name) ?: $name, 0, 1));
    }

    /**
     * The services it gives: what was typed, one to a line, blank lines left out.
     *
     * @return list<string>
     */
    public function serviceList(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $this->services) ?: [])));
    }

    /**
     * The people who work there, in the order given: only those with a name.
     *
     * @return list<array{name: string, position: ?string, photo: ?string}>
     */
    public function staff(): array
    {
        $staff = [];

        foreach ($this->people ?? [] as $person) {
            $name = is_array($person) ? trim((string) ($person['name'] ?? '')) : '';

            if ($name !== '') {
                $photo = $person['photo'] ?? null;

                $staff[] = [
                    'name' => $name,
                    'position' => trim((string) ($person['position'] ?? '')) ?: null,
                    'photo' => is_string($photo) && $photo !== '' ? Storage::disk('public')->url($photo) : null,
                ];
            }
        }

        return $staff;
    }

    /**
     * The paths of the photographs on its list of people.
     *
     * @return list<string>
     */
    public function peoplePhotos(): array
    {
        return array_values(array_filter(array_column(array_filter($this->people ?? [], 'is_array'), 'photo'), fn ($path) => is_string($path) && $path !== ''));
    }

    /**
     * Where else it is found, for those that have an address over HTTPS: [name => url].
     *
     * @return array<string, string>
     */
    public function links(): array
    {
        return array_filter(
            ['Facebook' => $this->facebook_url, 'LINE' => $this->line_url],
            fn (?string $url) => is_string($url) && preg_match('~^https://[^\s"\'<>]+$~', $url) === 1,
        );
    }

    /**
     * Every picture it has posted, newest first: the picture of each announcement, then the photographs of its
     * album. Each knows the announcement it came from.
     *
     * @return Collection<int, array{url: string, to: string, title: string}>
     */
    public function photos(int $limit = 60): Collection
    {
        $disk = Storage::disk('public');

        return $this->announcements()->published()->inListOrder()
            ->where(fn (Builder $query) => $query->whereNotNull('image_path')->orWhereNotNull('gallery'))
            ->limit($limit)->get()
            ->flatMap(fn (Announcement $one) => collect([$one->image_path, ...($one->gallery ?? [])])->filter(fn ($path) => is_string($path) && $path !== '')
                ->map(fn (string $path) => ['url' => $disk->url($path), 'to' => route('announcements.show', $one), 'title' => $one->localTitle()]))
            ->take($limit)->values();
    }

    /** The number as a link a phone can dial: the first one, when two are written ("044-579076 ต่อ 101"). */
    public function dialUrl(): ?string
    {
        if (blank($this->phone)) {
            return null;
        }

        preg_match('/[0-9+][0-9 \-]{4,}/', $this->phone, $found);
        $digits = preg_replace('/[^0-9+]/', '', $found[0] ?? '');

        return $digits !== '' ? 'tel:'.$digits : null;
    }
}
