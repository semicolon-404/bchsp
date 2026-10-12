<?php

namespace App\Filament\Resources\Announcements\Pages;

use App\Filament\Resources\Announcements\AnnouncementResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAnnouncement extends EditRecord
{
    protected static string $resource = AnnouncementResource::class;

    /** The account of a department cannot hand what it wrote to another department. */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (auth()->user()->postsForOneDepartment()) {
            $data['department_id'] = auth()->user()->department_id;
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
