<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\AdminOnly;
use App\Filament\Concerns\TranslatesTitle;
use App\Support\SystemHealth;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use UnitEnum;

/**
 * Whether the things that must be right on the server are right: the database,
 * the scheduled work, last night's backup, room on the disk (App\Support\SystemHealth).
 * For an administrator to look at now and then, and first when something seems off.
 */
class SystemStatus extends Page
{
    use AdminOnly;
    use TranslatesTitle;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'System status';

    protected static ?string $title = 'System status';

    protected static ?int $navigationSort = 9;

    protected string $view = 'filament.pages.system-status';

    /** A red number in the menu while something is wrong, so an administrator sees it without opening the page. */
    public static function getNavigationBadge(): ?string
    {
        $problems = SystemHealth::problems();

        return $problems > 0 ? (string) $problems : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    /** @return list<array{name: string, state: string, found: string, advice: ?string}> */
    public function getChecks(): array
    {
        return SystemHealth::checks();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backupNow')
                ->label(__('Take a backup now'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->requiresConfirmation()
                ->modalDescription(__('Copies the database and the uploaded files to this server\'s backup folder. It can take a minute.'))
                ->action(function () {
                    $failed = Artisan::call('backup:database') !== 0;

                    $failed
                        ? Notification::make()->danger()->title(__('The backup failed'))->body(trim(Artisan::output()))->persistent()->send()
                        : Notification::make()->success()->title(__('Backup taken'))->send();
                }),
        ];
    }
}
