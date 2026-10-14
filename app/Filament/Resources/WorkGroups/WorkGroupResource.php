<?php

namespace App\Filament\Resources\WorkGroups;

use App\Filament\Concerns\AdminOnly;
use App\Filament\Concerns\TranslatesNames;
use App\Filament\Resources\WorkGroups\Pages\ManageWorkGroups;
use App\Models\WorkGroup;
use App\Support\OptimizedUpload;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
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
 * About us. Drag the rows to put them in order. The director is in Site settings > Organisation.
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
                TextInput::make('head_name')
                    ->label(__('Head of the group'))
                    ->maxLength(150)
                    ->helperText(__('With the title, as it should be read. Someone who heads two groups is shown once on "Executives", with both.')),
                TextInput::make('head_position')
                    ->label(__('Profession or position of the head (optional)'))
                    ->maxLength(150)
                    ->placeholder(__('Registered nurse')),
                OptimizedUpload::on(FileUpload::make('head_photo_path'))
                    ->label(__('Photograph of the head (optional)'))
                    ->image()
                    ->imageEditor()
                    ->imageCropAspectRatio('1:1')
                    // No SVG: it can carry a script.
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->disk('public')
                    ->directory('people')
                    ->visibility('public')
                    ->maxSize(4096)
                    ->helperText(__('Square, the face in the middle. Ask the person first. Without one, the first letter of the name stands in.'))
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
            ->columns([
                ImageColumn::make('head_photo_path')
                    ->label('')
                    ->disk('public')
                    ->circular()
                    ->imageSize(36),
                TextColumn::make('name')
                    ->label(__('Name of the group'))
                    ->searchable()
                    ->weight('medium')
                    ->wrap(),
                TextColumn::make('head_name')
                    ->label(__('Head of the group'))
                    ->description(fn (WorkGroup $record) => $record->head_position)
                    ->searchable()
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
