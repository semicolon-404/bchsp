<?php

namespace App\Filament\Resources\Announcements\Pages;

use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Models\Announcement;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAnnouncement extends EditRecord
{
    protected static string $resource = AnnouncementResource::class;

    /** One that is out is offered to the account of a department as "Send for approval": that is what saving it will do. */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (Announcement::waitsForApproval(auth()->user()) && ($data['status'] ?? null) === Announcement::STATUS_PUBLISHED) {
            $data['status'] = Announcement::STATUS_PENDING;
        }

        return $data;
    }

    /** The account of a department cannot hand what it wrote to another department. */
    protected function mutateFormDataBeforeSave(array $data): array
    {
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

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
