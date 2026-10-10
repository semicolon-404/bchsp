<?php

namespace App\Filament\Resources\AccessLogs;

use App\Filament\Concerns\AdminOnly;
use App\Filament\Concerns\TranslatesNames;
use App\Filament\Resources\AccessLogs\Pages\ManageAccessLogs;
use App\Models\AccessLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * The access log, for an administrator to read: sign-ins, and what staff
 * changed in this panel (App\Support\StaffLog). Nothing here can be added,
 * edited or removed by hand.
 */
class AccessLogResource extends Resource
{
    use AdminOnly, TranslatesNames;

    protected static ?string $model = AccessLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Access log';

    protected static ?string $modelLabel = 'log entry';

    protected static ?string $pluralModelLabel = 'access log';

    protected static ?int $navigationSort = 8;

    /** Event => what the panel calls it. */
    public const EVENTS = [
        'login' => 'Signed in',
        'logout' => 'Signed out',
        'login_failed' => 'Failed sign-in',
        'admin.created' => 'Created',
        'admin.updated' => 'Changed',
        'admin.deleted' => 'Deleted',
        'admin.settings' => 'Changed settings',
    ];

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('When'))
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label(__('Account'))
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('event')
                    ->label(__('What'))
                    ->formatStateUsing(fn (string $state) => __(self::EVENTS[$state] ?? $state))
                    ->badge()
                    ->color(fn (string $state) => match (true) {
                        $state === 'login_failed' => 'danger',
                        str_starts_with($state, 'admin.') => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('subject_id')
                    ->label(__('Item'))
                    ->formatStateUsing(fn (AccessLog $record) => $record->subject_type.' #'.$record->subject_id)
                    ->placeholder('—'),
                TextColumn::make('identifier')
                    ->label(__('Details'))
                    ->wrap()
                    ->placeholder('—'),
                TextColumn::make('ip_address')
                    ->label(__('IP address'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('user_agent')
                    ->label(__('Browser'))
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('staff')
                    ->label(__('Only what was changed in this panel'))
                    ->query(fn (Builder $query) => $query->where('event', 'like', 'admin.%')),
                SelectFilter::make('event')
                    ->label(__('What'))
                    ->options(array_map('__', self::EVENTS)),
                SelectFilter::make('user')
                    ->label(__('Account'))
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAccessLogs::route('/'),
        ];
    }
}
