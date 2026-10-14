<?php

namespace App\Filament\Resources\WorkGroups\Pages;

use App\Filament\Resources\WorkGroups\WorkGroupResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;

class ManageWorkGroups extends ManageRecords
{
    protected static string $resource = WorkGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')
                ->label(__('View on the site'))
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn () => url('/p/structure'))
                ->openUrlInNewTab(),
            // A new one goes to the end of the list.
            CreateAction::make()->mutateDataUsing(fn (array $data) => $data + ['sort_order' => (int) WorkGroupResource::getModel()::max('sort_order') + 1]),
        ];
    }
}
