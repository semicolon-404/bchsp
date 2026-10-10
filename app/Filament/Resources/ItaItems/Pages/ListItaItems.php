<?php

namespace App\Filament\Resources\ItaItems\Pages;

use App\Filament\Resources\ItaItems\ItaItemResource;
use App\Models\ItaItem;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListItaItems extends ListRecords
{
    protected static string $resource = ItaItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // The items are much the same from one year to the next: copy the headings, then add this year's evidence.
            Action::make('startYear')
                ->label(__('Start a new year'))
                ->icon(Heroicon::OutlinedDocumentDuplicate)
                ->color('gray')
                ->visible(fn () => ItaItem::exists())
                ->modalDescription(__('Copies the numbers and headings of a year to a new one, with no documents. Nothing is copied when the new year already has items.'))
                ->schema([
                    TextInput::make('from')
                        ->label(__('Copy the headings of'))
                        ->numeric()
                        ->integer()
                        ->default(fn () => ItaItem::max('year'))
                        ->required(),
                    TextInput::make('to')
                        ->label(__('New fiscal year'))
                        ->numeric()
                        ->integer()
                        ->minValue(2550)
                        ->maxValue(2700)
                        ->default(fn () => (int) ItaItem::max('year') + 1)
                        ->different('from')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $made = ItaItem::startYearFrom((int) $data['from'], (int) $data['to']);

                    $made > 0
                        ? Notification::make()->success()->title(__(':count items made for :year', ['count' => $made, 'year' => $data['to']]))->send()
                        : Notification::make()->warning()->title(__('Nothing was copied'))->body(__('That year has no items to copy, or the new year already has some.'))->send();
                }),
            CreateAction::make(),
        ];
    }
}
