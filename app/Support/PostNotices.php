<?php

namespace App\Support;

use App\Enums\Role;
use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Models\Announcement;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The bell in the panel, for the posts of the departments (Announcement::booted, "created" and "updated").
 *
 * A post sent for approval tells those who can approve it; one approved, or sent back, tells the accounts
 * of the department it belongs to. Whoever did the thing is not told of it.
 */
class PostNotices
{
    /** $before: where the post stood until now; null for one just written. */
    public static function tell(Announcement $post, ?string $before): void
    {
        match (true) {
            $post->status === Announcement::STATUS_PENDING => self::send(
                self::reviewers(),
                __('A post is waiting for approval'),
                collect([$post->title, $post->department?->name])->filter()->implode(' · '),
                'heroicon-o-check-badge', 'warning', $post, __('Look at it'),
            ),
            $post->status === Announcement::STATUS_RETURNED => self::send(
                self::of($post),
                __('A post was sent back for changes'),
                $post->title.(filled($post->review_note) ? ': '.Str::limit((string) $post->review_note, 160) : ''),
                'heroicon-o-arrow-uturn-left', 'danger', $post, __('Open the post'),
            ),
            $post->status === Announcement::STATUS_PUBLISHED && $before === Announcement::STATUS_PENDING => self::send(
                self::of($post),
                __('A post was approved'),
                $post->title,
                'heroicon-o-check-circle', 'success', $post, __('Open the post'),
            ),
            default => null,
        };
    }

    /** Those who post for the whole hospital: administrators, and authors with no department. */
    private static function reviewers(): Collection
    {
        return User::where(fn ($query) => $query->where('role', Role::Admin->value)->orWhereNull('department_id'))->get();
    }

    /** The accounts of the department the post belongs to. */
    private static function of(Announcement $post): Collection
    {
        return $post->department_id
            ? User::where('department_id', $post->department_id)->where('role', Role::Author->value)->get()
            : collect();
    }

    private static function send(Collection $people, string $title, string $body, string $icon, string $colour, Announcement $post, string $open): void
    {
        $people = $people->reject(fn (User $user) => $user->is(auth()->user()));

        if ($people->isEmpty()) {
            return;
        }

        Notification::make()
            ->title($title)
            ->body($body)
            ->icon($icon)
            ->iconColor($colour)
            ->actions([
                Action::make('open')->label($open)->url(AnnouncementResource::getUrl('edit', ['record' => $post], panel: 'admin'))->markAsRead(),
            ])
            ->sendToDatabase($people);
    }
}
