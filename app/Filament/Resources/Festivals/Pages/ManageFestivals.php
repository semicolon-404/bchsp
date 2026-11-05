<?php

namespace App\Filament\Resources\Festivals\Pages;

use App\Filament\Resources\Festivals\FestivalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFestivals extends ManageRecords
{
    protected static string $resource = FestivalResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
