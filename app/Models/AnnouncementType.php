<?php

namespace App\Models;

use App\Models\Concerns\InTwoLanguages;
use App\Support\Slug;
use Database\Factories\AnnouncementTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** What kind of announcement it is: news, procurement, job openings. Shown as a filter above the list. */
class AnnouncementType extends Model
{
    /** @use HasFactory<AnnouncementTypeFactory> */
    use HasFactory, InTwoLanguages;

    /** The symbols a kind can wear (Heroicons) => what the panel calls each. */
    public const ICONS = [
        'megaphone' => 'Megaphone (news)',
        'clipboard-document-list' => 'Clipboard (procurement)',
        'briefcase' => 'Briefcase (jobs)',
        'bell-alert' => 'Bell (notice)',
        'document-text' => 'Document',
        'calendar-days' => 'Calendar (events)',
        'academic-cap' => 'Cap (training)',
        'banknotes' => 'Banknotes (finance)',
        'user-group' => 'People',
        'heart' => 'Heart (health)',
        'scale' => 'Scales (regulations)',
        'trophy' => 'Trophy (awards)',
    ];

    /** The colours a kind can wear => [what the panel calls it, the site's colour it is drawn in (resources/css/app.css)]. */
    public const TONES = [
        'accent' => ['The colour of the site', 'var(--color-brand)'],
        'green' => ['Green', 'var(--color-syn-function)'],
        'yellow' => ['Yellow', 'var(--color-syn-string)'],
        'purple' => ['Purple', 'var(--color-syn-variable)'],
        'pink' => ['Pink', 'var(--color-syn-keyword)'],
        'red' => ['Red', 'var(--color-danger)'],
        'grey' => ['Grey', 'var(--color-muted)'],
    ];

    protected $fillable = ['name', 'name_en', 'slug', 'icon', 'tone', 'sort_order'];

    protected $attributes = ['icon' => 'megaphone', 'tone' => 'accent', 'sort_order' => 0];

    protected static function booted(): void
    {
        static::saving(function (AnnouncementType $type) {
            $type->slug = Slug::unique($type, $type->slug ?: $type->name, 'type');
        });
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    /** Its symbol: one of ICONS, whatever is stored. */
    public function icon(): string
    {
        return isset(self::ICONS[$this->icon]) ? $this->icon : 'megaphone';
    }

    /** Its colour, as CSS: one of TONES, whatever is stored. Follows the light and dark looks by itself. */
    public function colour(): string
    {
        return (self::TONES[$this->tone] ?? self::TONES['accent'])[1];
    }

    /** The name in the reader's language. */
    public function localName(): string
    {
        return $this->inLanguage('name');
    }
}
