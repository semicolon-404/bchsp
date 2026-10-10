<?php

namespace App\Models;

use Database\Factories\StaffLinkFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A service staff reach from the "For staff" page (/staff): the document
 * cabinet, the repair request system, a dashboard. Only a link is kept here;
 * each service has its own sign-in. Many answer only inside the hospital's network.
 */
class StaffLink extends Model
{
    /** @use HasFactory<StaffLinkFactory> */
    use HasFactory;

    protected $fillable = ['title', 'url', 'group', 'note', 'is_active', 'sort_order'];

    protected $attributes = ['is_active' => true, 'sort_order' => 0];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** Shown on the page, in the administrator's order. */
    public function scopeShown(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order')->orderBy('title');
    }

    /** Only a web address is ever put into a link. */
    public function safeUrl(): ?string
    {
        return preg_match('~^https?://~i', (string) $this->url) ? $this->url : null;
    }
}
