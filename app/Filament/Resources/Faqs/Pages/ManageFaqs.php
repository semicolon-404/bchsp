<?php

namespace App\Filament\Resources\Faqs\Pages;

use App\Filament\Resources\Faqs\FaqResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFaqs extends ManageRecords
{
    protected static string $resource = FaqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // A new one goes to the end of the list.
            CreateAction::make()->mutateDataUsing(fn (array $data) => $data + ['sort_order' => (int) FaqResource::getModel()::max('sort_order') + 1]),
        ];
    }
}
