<?php

namespace App\Filament\Resources\People;

use App\Filament\Concerns\AdminOnly;
use App\Filament\Concerns\TranslatesNames;
use App\Filament\Resources\People\Pages\ManagePeople;
use App\Models\Person;
use App\Support\OptimizedUpload;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * The register of the people the site names: each once, with a position and a photograph. A group of work
 * names its head from here, and Site settings names the director from here, so a photograph is given once
 * and shows wherever the person appears ("Organisation structure", "Executives", "About us").
 */
class PersonResource extends Resource
{
    use AdminOnly, TranslatesNames;

    protected static ?string $model = Person::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Hospital';

    protected static ?string $navigationLabel = 'Personnel';

    protected static ?string $modelLabel = 'person';

    protected static ?string $pluralModelLabel = 'personnel';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'name';

    /** The fields of one person; also what a group of work shows to add a head who is not in the register yet. */
    public static function fields(): array
    {
        return [
            TextInput::make('name')
                ->label(__('Name'))
                ->required()
                ->maxLength(150)
                ->helperText(__('With the title, as it should be read.')),
            TextInput::make('position')
                ->label(__('Profession or position (optional)'))
                ->maxLength(150)
                ->placeholder(__('Registered nurse')),
            OptimizedUpload::on(FileUpload::make('photo_path'))
                ->label(__('Photograph (optional)'))
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
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                ...self::fields(),
                Select::make('work_group_id')
                    ->label(__('Group of work (optional)'))
                    ->relationship('workGroup', 'name', fn ($query) => $query->orderBy('sort_order')->orderBy('name'))
                    ->searchable()
                    ->preload(),
                Toggle::make('is_active')
                    ->label(__('Still at the hospital'))
                    ->helperText(__('Switched off, the person is kept in the register and no longer offered when choosing a head.'))
                    ->default(true)
                    ->inline(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->modifyQueryUsing(fn ($query) => $query->with('workGroup')->withCount('heads'))
            ->columns([
                ImageColumn::make('photo_path')
                    ->label('')
                    ->disk('public')
                    ->circular()
                    ->imageSize(40),
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->description(fn (Person $record) => $record->position)
                    ->searchable(['name', 'position'])
                    ->weight('medium'),
                TextColumn::make('workGroup.name')
                    ->label(__('Group of work'))
                    ->placeholder('—')
                    ->wrap(),
                TextColumn::make('heads_count')
                    ->label(__('Heads'))
                    ->formatStateUsing(fn (int $state) => trans_choice('{1} :count group|[2,*] :count groups', $state, ['count' => $state]))
                    ->badge()
                    ->color('success')
                    ->state(fn (Person $record) => $record->heads_count ?: null)
                    ->placeholder(''),
                ToggleColumn::make('is_active')
                    ->label(__('Still at the hospital')),
            ])
            ->filters([
                SelectFilter::make('work_group_id')
                    ->label(__('Group of work'))
                    ->relationship('workGroup', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePeople::route('/'),
        ];
    }
}
