<?php

namespace App\Models;

use App\Support\PostNotices;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A comment the staff leave under a post in the panel (the post's "Comments"): a few words, pictures if they
 * like. Only the panel shows them. Those taking part are told of a new one by the bell (PostNotices).
 */
class AnnouncementComment extends Model
{
    protected $fillable = ['announcement_id', 'user_id', 'body', 'images'];

    protected function casts(): array
    {
        return ['images' => 'array'];
    }

    protected static function booted(): void
    {
        static::created(fn (self $comment) => PostNotices::tellOfComment($comment));

        // Its pictures go with it.
        static::deleted(fn (self $comment) => Storage::disk('public')->delete(array_filter($comment->images ?? [], 'is_string')));
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
