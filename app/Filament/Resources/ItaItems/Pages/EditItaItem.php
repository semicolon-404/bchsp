<?php

namespace App\Filament\Resources\ItaItems\Pages;

use App\Filament\Resources\ItaItems\ItaItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditItaItem extends EditRecord
{
    protected static string $resource = ItaItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
