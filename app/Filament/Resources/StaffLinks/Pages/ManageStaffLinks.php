<?php

namespace App\Filament\Resources\StaffLinks\Pages;

use App\Filament\Resources\StaffLinks\StaffLinkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageStaffLinks extends ManageRecords
{
    protected static string $resource = StaffLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // A new one goes to the end of the list.
            CreateAction::make()->mutateDataUsing(fn (array $data) => $data + ['sort_order' => (int) StaffLinkResource::getModel()::max('sort_order') + 1]),
        ];
    }
}
