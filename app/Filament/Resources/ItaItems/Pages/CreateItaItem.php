<?php

namespace App\Filament\Resources\ItaItems\Pages;

use App\Filament\Resources\ItaItems\ItaItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateItaItem extends CreateRecord
{
    protected static string $resource = ItaItemResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
