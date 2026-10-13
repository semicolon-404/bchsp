<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Models\Announcement;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

/**
 * The newest posts on the panel's first page, each with where it stands (showing, scheduled, waiting for
 * approval, sent back, a draft) and leading to where it is edited. The account of a department sees its own.
 */
class LatestPosts extends Widget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.latest-posts';

    /** @return Collection<int, Announcement> */
    public function getPosts(): Collection
    {
        $user = auth()->user();

        return Announcement::with('type', 'department')
            ->when($user->postsForOneDepartment(), fn ($query) => $query->where('department_id', $user->department_id))
            ->latest('updated_at')->limit(6)->get();
    }

    public function getAllUrl(): string
    {
        return AnnouncementResource::getUrl('index');
    }

    public function colorOf(Announcement $post): string
    {
        return match ($post->state()) {
            'Showing' => 'success',
            'Scheduled' => 'warning',
            'Waiting for approval', 'Sent back' => 'danger',
            default => 'gray',
        };
    }
}
