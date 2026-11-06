<?php

namespace App\Filament\Resources\ClinicSessions\Pages;

use App\Filament\Resources\ClinicSessions\ClinicSessionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageClinicSessions extends ManageRecords
{
    protected static string $resource = ClinicSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->mutateDataUsing(fn (array $data) => ClinicSessionResource::ownDepartment($data)),
        ];
    }
}
