<?php

namespace App\Filament\Resources\Krathongs\Pages;

use App\Filament\Resources\Krathongs\KrathongResource;
use App\Models\Krathong;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageKrathongs extends ManageRecords
{
    protected static string $resource = KrathongResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Floated by whoever writes it, for their department; a department floats one to a festival.
            CreateAction::make()
                ->label(__('Float a krathong'))
                ->mutateDataUsing(fn (array $data) => $data + ['user_id' => auth()->id(), 'department_id' => auth()->user()->department_id])
                ->before(function (CreateAction $action, array $data) {
                    $department = auth()->user()->department_id;

                    if ($department && Krathong::where('festival_id', $data['festival_id'])->where('department_id', $department)->exists()) {
                        Notification::make()->title(__('Your department has floated its krathong already: edit it instead.'))->warning()->send();
                        $action->halt();
                    }
                }),
        ];
    }
}
