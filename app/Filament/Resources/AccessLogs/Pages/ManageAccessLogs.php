<?php

namespace App\Filament\Resources\AccessLogs\Pages;

use App\Filament\Resources\AccessLogs\AccessLogResource;
use Filament\Resources\Pages\ManageRecords;

class ManageAccessLogs extends ManageRecords
{
    protected static string $resource = AccessLogResource::class;
}
