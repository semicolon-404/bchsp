<?php

namespace App\Filament\Resources\SiteUpdates;

use App\Filament\Concerns\AdminOnly;
use App\Filament\Concerns\TranslatesNames;
use App\Filament\Resources\SiteUpdates\Pages\ManageSiteUpdates;
use App\Models\SiteUpdate;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * What has changed on the website, release by release, as the public reads it at /updates:
 * a number, a day, and the changes, each marked new, improved or fixed. One that is
 * "being worked on" is shown apart with no day promised.
 */
class SiteUpdateResource extends Resource
{
    use AdminOnly, TranslatesNames;

    protected static ?string $model = SiteUpdate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    /** Under System status in the menu, shown as that is opened: the sidebar stays short. */
    public static function getNavigationParentItem(): ?string
    {
        return __(\App\Filament\Pages\SystemStatus::getNavigationLabel());
    }

    protected static ?string $navigationLabel = 'Website updates';

    protected static ?string $modelLabel = 'update';

    protected static ?string $pluralModelLabel = 'website updates';

    protected static ?int $navigationSort = 8;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                TextInput::make('version')
                    ->label(__('Release number'))
                    ->required()
                    ->maxLength(20)
                    ->placeholder('1.0')
                    ->helperText(__('As the footer of the site shows it.')),
                Select::make('status')
                    ->label(__('Status'))
                    ->options([SiteUpdate::STATUS_RELEASED => __('Released'), SiteUpdate::STATUS_PLANNED => __('Being worked on')])
                    ->default(SiteUpdate::STATUS_RELEASED)
                    ->required()
                    ->selectablePlaceholder(false)
                    ->live(),
                DatePicker::make('released_on')
                    ->label(__('Released on'))
                    ->default(fn () => now())
                    ->visible(fn ($get) => $get('status') !== SiteUpdate::STATUS_PLANNED)
                    ->helperText(__('A day still to come keeps it off the site until then.')),
                TextInput::make('title')
                    ->label(__('In a few words'))
                    ->required()
                    ->maxLength(200)
                    ->columnSpan(2),
                TextInput::make('title_en')
                    ->label(__('In English (optional)'))
                    ->maxLength(200),
                Repeater::make('items')
                    ->label(__('What changed'))
                    ->helperText(__('One change to a line, in words a visitor understands: what they can now do, not how it was made. Up to :count.', ['count' => SiteUpdate::MAX_ITEMS]))
                    ->schema([
                        Select::make('kind')
                            ->label(__('Kind'))
                            ->options(collect(SiteUpdate::KINDS)->map(fn (array $kind) => __($kind[0]))->all())
                            ->default('new')
                            ->required()
                            ->selectablePlaceholder(false),
                        Textarea::make('text')->label(__('In Thai'))->rows(2)->required()->maxLength(300),
                        Textarea::make('text_en')->label(__('In English'))->rows(2)->maxLength(300),
                    ])
                    ->columns(3)
                    ->defaultItems(1)
                    ->maxItems(SiteUpdate::MAX_ITEMS)
                    ->addActionLabel(__('Add a change'))
                    ->reorderableWithButtons()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('released_on', 'desc')
            ->columns([
                TextColumn::make('version')
                    ->label(__('Release number'))
                    ->badge(),
                TextColumn::make('title')
                    ->label(__('In a few words'))
                    ->searchable()
                    ->wrap(),
                TextColumn::make('items')
                    ->label(__('What changed'))
                    ->state(fn (SiteUpdate $record) => count($record->changes())),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->formatStateUsing(fn (string $state) => __($state === SiteUpdate::STATUS_PLANNED ? 'Being worked on' : 'Released'))
                    ->badge()
                    ->color(fn (string $state) => $state === SiteUpdate::STATUS_PLANNED ? 'warning' : 'success'),
                TextColumn::make('released_on')
                    ->label(__('Released on'))
                    ->date('j M Y')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSiteUpdates::route('/'),
        ];
    }
}
