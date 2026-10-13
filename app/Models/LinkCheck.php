<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** What the look at one of an announcement's links to a file kept elsewhere found (App\Support\LinkChecks). */
class LinkCheck extends Model
{
    public const OK = 'ok';

    // The file is gone: whoever presses the link gets an error page.
    public const DEAD = 'dead';

    // The file is there but asks the reader to sign in: it was not shared with everyone.
    public const PRIVATE = 'private';

    public $timestamps = false;

    protected $fillable = ['announcement_id', 'url', 'url_hash', 'state', 'checked_at'];

    protected function casts(): array
    {
        return ['checked_at' => 'datetime'];
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }
}
