<?php

namespace App\Filament\Resources\Departments\Pages;

use App\Filament\Resources\Departments\DepartmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageDepartments extends ManageRecords
{
    protected static string $resource = DepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Adding one is the administrator's. A new one goes to the end of the list.
            CreateAction::make()->visible(fn () => DepartmentResource::canCreate())->mutateDataUsing(fn (array $data) => $data + ['sort_order' => (int) DepartmentResource::getModel()::max('sort_order') + 1]),
        ];
    }
}
