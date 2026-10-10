<?php

namespace App\Filament\Resources\AnnouncementTypes\Pages;

use App\Filament\Resources\AnnouncementTypes\AnnouncementTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAnnouncementTypes extends ManageRecords
{
    protected static string $resource = AnnouncementTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
