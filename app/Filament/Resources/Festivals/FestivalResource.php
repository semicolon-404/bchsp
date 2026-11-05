<?php

namespace App\Filament\Resources\Festivals;

use App\Filament\Concerns\NotForDepartments;
use App\Filament\Resources\Festivals\Pages\ManageFestivals;
use App\Models\Festival;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * The festivals the home page dresses up for, each between two days (App\Models\Festival). Whole hospital: the
 * administrators set them; the departments float their krathongs (KrathongResource).
 */
class FestivalResource extends Resource
{
    use NotForDepartments;

    protected static ?string $model = Festival::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Festivals';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return __('Festivals');
    }

    public static function getModelLabel(): string
    {
        return __('festival');
    }

    public static function getPluralModelLabel(): string
    {
        return __('festivals');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(120)
                    ->placeholder(__('Loy Krathong 2570'))
                    ->columnSpanFull(),
                Select::make('theme')
                    ->label(__('Theme'))
                    ->options(collect(Festival::THEMES)->map(fn ($label) => __($label))->all())
                    ->required()
                    ->native(false),
                Radio::make('intensity')
                    ->label(__('How much moves'))
                    ->options(collect(Festival::INTENSITIES)->map(fn ($label) => __($label))->all())
                    ->default('medium')
                    ->inline(),
                DatePicker::make('starts_on')
                    ->label(__('From'))
                    ->required()
                    ->native(false),
                DatePicker::make('ends_on')
                    ->label(__('To'))
                    ->required()
                    ->afterOrEqual('starts_on')
                    ->native(false),
                Toggle::make('is_active')
                    ->label(__('On'))
                    ->helperText(__('Off, the home page stays as it is even within the days.'))
                    ->default(true)
                    ->inline(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('starts_on', 'desc')
            ->columns([
                TextColumn::make('name')->label(__('Name'))->searchable()->weight('semibold'),
                TextColumn::make('theme')->label(__('Theme'))->badge()->formatStateUsing(fn (string $state) => __(Festival::THEMES[$state] ?? $state)),
                TextColumn::make('starts_on')->label(__('From'))->date('j M Y'),
                TextColumn::make('ends_on')->label(__('To'))->date('j M Y'),
                TextColumn::make('krathongs_count')->label(__('Krathongs'))->counts('krathongs'),
                ToggleColumn::make('is_active')->label(__('On')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageFestivals::route('/')];
    }
}
