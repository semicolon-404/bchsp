<?php

namespace App\Filament\Resources\SiteUpdates\Pages;

use App\Filament\Resources\SiteUpdates\SiteUpdateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSiteUpdates extends ManageRecords
{
    protected static string $resource = SiteUpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
