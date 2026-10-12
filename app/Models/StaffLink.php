<?php

namespace App\Models;

use Database\Factories\StaffLinkFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A link to a service that lives elsewhere. For staff, on the "For staff" page (/staff): the
 * document cabinet, the repair request system, a dashboard; many answer only inside the hospital's
 * network. Or for the public, on "Services online" (/services): booking a visit, the ministry's
 * applications. Only a link is kept here; each service has its own sign-in.
 */
class StaffLink extends Model
{
    /** @use HasFactory<StaffLinkFactory> */
    use HasFactory;

    /** Who a link is for => what the panel calls it. */
    public const AUDIENCES = ['staff' => 'Staff (the "For staff" page)', 'public' => 'The public ("Services online")'];

    protected $fillable = ['title', 'url', 'audience', 'group', 'note', 'is_active', 'sort_order'];

    protected $attributes = ['audience' => 'staff', 'is_active' => true, 'sort_order' => 0];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** Shown on the "For staff" page, in the administrator's order. */
    public function scopeShown(Builder $query): void
    {
        $query->where('audience', 'staff')->where('is_active', true)->orderBy('sort_order')->orderBy('title');
    }

    /** Shown on "Services online", in the administrator's order. */
    public function scopeForThePublic(Builder $query): void
    {
        $query->where('audience', 'public')->where('is_active', true)->orderBy('sort_order')->orderBy('title');
    }

    /** Only a web address is ever put into a link. */
    public function safeUrl(): ?string
    {
        return preg_match('~^https?://~i', (string) $this->url) ? $this->url : null;
    }
}
