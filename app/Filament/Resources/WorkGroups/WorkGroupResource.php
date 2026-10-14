<?php

namespace App\Filament\Resources\WorkGroups;

use App\Filament\Concerns\AdminOnly;
use App\Filament\Concerns\TranslatesNames;
use App\Filament\Resources\People\PersonResource;
use App\Filament\Resources\WorkGroups\Pages\ManageWorkGroups;
use App\Models\WorkGroup;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * The groups of work of the hospital as its organisation chart has them, each with its head, how many work
 * in it and the units of work inside it. They make the pages "Organisation structure" and "Executives" under
 * About us. Drag the rows to put them in order. A head is chosen from the register of people (Hospital >
 * Personnel); the director is chosen in Site settings > Organisation.
 */
class WorkGroupResource extends Resource
{
    use AdminOnly, TranslatesNames;

    protected static ?string $model = WorkGroup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Hospital';

    protected static ?string $navigationLabel = 'Organisation structure';

    protected static ?string $modelLabel = 'group of work';

    protected static ?string $pluralModelLabel = 'groups of work';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label(__('Name of the group'))
                    ->required()
                    ->maxLength(150)
                    ->placeholder(__('Nursing group')),
                TextInput::make('name_en')
                    ->label(__('Name in English (optional)'))
                    ->maxLength(150),
                Select::make('head_id')
                    ->label(__('Head of the group'))
                    ->relationship('head', 'name', fn ($query) => $query->where('is_active', true)->orderBy('sort_order')->orderBy('name'))
                    ->searchable()
                    ->preload()
                    // Someone not in the register yet is added to it from here, and is then there for every other place.
                    ->createOptionForm(PersonResource::fields())
                    ->createOptionModalHeading(__('Add a person to the register'))
                    ->helperText(__('Chosen from Personnel, where the name, position and photograph are kept once. Someone who heads two groups is shown once on "Executives", with both.'))
                    ->columnSpanFull(),
                TextInput::make('staff_count')
                    ->label(__('People working in it (optional)'))
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(5000),
                Select::make('department_id')
                    ->label(__('Its page on this site (optional)'))
                    ->relationship('department', 'name', fn ($query) => $query->orderBy('sort_order')->orderBy('name'))
                    ->searchable()
                    ->preload()
                    ->helperText(__('When one of the departments of the site stands for this group, the group leads to its profile.')),
                TagsInput::make('units')
                    ->label(__('Units of work inside it'))
                    ->placeholder(__('Type a name and press Enter'))
                    ->helperText(__('Each as the organisation chart names it, in the order to show them.'))
                    ->reorderable()
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label(__('Shown on the site'))
                    ->default(true)
                    ->inline(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->modifyQueryUsing(fn ($query) => $query->with('head'))
            ->columns([
                ImageColumn::make('head.photo_path')
                    ->label('')
                    ->disk('public')
                    ->circular()
                    ->imageSize(36),
                TextColumn::make('name')
                    ->label(__('Name of the group'))
                    ->searchable()
                    ->weight('medium')
                    ->wrap(),
                TextColumn::make('head')
                    ->label(__('Head of the group'))
                    ->state(fn (WorkGroup $record) => $record->headName())
                    ->description(fn (WorkGroup $record) => $record->headPosition())
                    ->placeholder('—'),
                TextColumn::make('staff_count')
                    ->label(__('People working here'))
                    ->numeric()
                    ->placeholder('—'),
                TextColumn::make('units')
                    ->label(__('Units of work'))
                    ->state(fn (WorkGroup $record) => count($record->unitList()))
                    ->numeric(),
                ToggleColumn::make('is_active')
                    ->label(__('Shown on the site')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageWorkGroups::route('/'),
        ];
    }
}
