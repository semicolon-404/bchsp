<?php

namespace App\Filament\Resources\Announcements\Pages;

use App\Filament\Resources\Announcements\AnnouncementResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Support\Icons\Heroicon;
use Filament\Resources\Pages\ListRecords;

class ListAnnouncements extends ListRecords
{
    protected static string $resource = AnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        $department = auth()->user()?->department;

        return [
            // Everything the department put out, as titles by month on the public site: what readers see of it.
            Action::make('onTheSite')
                ->label(__('See them on the site'))
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(route('announcements.index', array_filter(['department' => $department?->is_active ? $department->slug : null, 'view' => 'index'])))
                ->openUrlInNewTab(),
            CreateAction::make(),
        ];
    }
}
