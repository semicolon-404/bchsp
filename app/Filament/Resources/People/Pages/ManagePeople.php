<?php

namespace App\Filament\Resources\People\Pages;

use App\Filament\Resources\People\PersonResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePeople extends ManageRecords
{
    protected static string $resource = PersonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // A new one goes to the end of the list.
            CreateAction::make()->mutateDataUsing(fn (array $data) => PersonResource::ownGroup($data) + ['sort_order' => (int) PersonResource::getModel()::max('sort_order') + 1]),
        ];
    }
}
