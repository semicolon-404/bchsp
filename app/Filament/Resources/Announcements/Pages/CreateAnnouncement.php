<?php

namespace App\Filament\Resources\Announcements\Pages;

use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Models\Announcement;
use Filament\Resources\Pages\CreateRecord;

class CreateAnnouncement extends CreateRecord
{
    protected static string $resource = AnnouncementResource::class;

    /** Whoever writes it is its author, and the account of a department writes for that department. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['author_id'] = auth()->id();

        // The account of a department posts for that department, whatever was sent.
        if (auth()->user()->postsForOneDepartment()) {
            $data['department_id'] = auth()->user()->department_id;
        }

        // While the hospital asks to see it first, the account of a department cannot put its own writing out:
        // anything but a draft waits for approval, a change to one already out as well.
        if (Announcement::waitsForApproval(auth()->user()) && ($data['status'] ?? null) !== Announcement::STATUS_DRAFT) {
            $data['status'] = Announcement::STATUS_PENDING;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
