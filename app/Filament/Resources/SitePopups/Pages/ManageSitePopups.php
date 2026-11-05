<?php

namespace App\Filament\Resources\SitePopups\Pages;

use App\Filament\Resources\SitePopups\SitePopupResource;
use App\Models\SitePopup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSitePopups extends ManageRecords
{
    protected static string $resource = SitePopupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // A new one goes to the end of the order.
            CreateAction::make()->mutateDataUsing(fn (array $data) => $data + ['sort_order' => (int) SitePopup::max('sort_order') + 1]),
        ];
    }
}
