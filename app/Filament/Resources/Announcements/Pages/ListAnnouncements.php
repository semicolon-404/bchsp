<?php

namespace App\Filament\Resources\Announcements\Pages;

use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Models\Announcement;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

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
            // Every file of a fiscal year's announcements in one zip, to keep apart from the site.
            Action::make('filesArchive')
                ->label(__('Download the files of a fiscal year'))
                ->icon(Heroicon::OutlinedArchiveBoxArrowDown)
                ->color('gray')
                ->visible(fn () => auth()->user()?->isAdmin())
                ->schema([
                    Select::make('year')
                        ->label(__('Fiscal year'))
                        ->options(fn () => collect(Announcement::fiscalYears())->mapWithKeys(fn ($count, $year) => [$year => $year.' ('.trans_choice('{1} :count announcement|[2,*] :count announcements', $count, ['count' => $count]).')'])->all())
                        ->default(Announcement::fiscalYearOf(now()))
                        ->required(),
                ])
                ->action(fn (array $data) => redirect()->route('files.archive', ['year' => $data['year']])),
            CreateAction::make(),
        ];
    }
}
