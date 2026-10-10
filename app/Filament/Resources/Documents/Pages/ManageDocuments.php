<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDocuments extends ManageRecords
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // A new one goes to the end of the list.
            CreateAction::make()->mutateDataUsing(fn (array $data) => $data + ['sort_order' => (int) DocumentResource::getModel()::max('sort_order') + 1]),
        ];
    }
}
