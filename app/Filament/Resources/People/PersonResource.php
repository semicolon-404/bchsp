<?php

namespace App\Filament\Resources\People;

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
use Filament\Forms\Components\ViewField;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Models\WorkGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use UnitEnum;

/**
 * The register of the people the site names: each once, with a position and a photograph. A group of work
 * names its head from here, and Site settings names the director from here, so a photograph is given once
 * and shows wherever the person appears ("Organisation structure", "Executives", "About us").
 */
class PersonResource extends Resource
{
    use TranslatesNames;

    /** An administrator; or a department's account, for the people of its own groups of work. */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->isAdmin() || $user?->postsForOneDepartment());
    }

    private static function forOneDepartment(): ?int
    {
        $user = auth()->user();

        return $user?->postsForOneDepartment() ? $user->department_id : null;
    }

    public static function getEloquentQuery(): Builder
    {
        $department = self::forOneDepartment();

        return parent::getEloquentQuery()->when($department, fn (Builder $query) => $query->whereHas('workGroup', fn (Builder $group) => $group->where('department_id', $department)));
    }

    /** What a department's account saves stays among its own groups of work, whatever the form held. */
    public static function ownGroup(array $data): array
    {
        $department = self::forOneDepartment();

        if ($department && ! WorkGroup::whereKey($data['work_group_id'] ?? 0)->where('department_id', $department)->exists()) {
            throw ValidationException::withMessages(['data.work_group_id' => __('Choose one of your own groups of work.')]);
        }

        return $data;
    }

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
                // No SVG: it can carry a script.
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->disk('public')
                ->directory('people')
                ->visibility('public')
                ->maxSize(4096)
                ->helperText(__('Any shape: the part that shows in the round picture is chosen below. Ask the person first. Without one, the first letter of the name stands in.'))
                ->columnSpanFull(),
            // Which part of it shows in the round picture: dragged into place (resources/views/filament/photo-focus.blade.php).
            ViewField::make('photo_focus')
                ->label(__('Part of the photograph shown'))
                ->view('filament.photo-focus')
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
                    ->relationship('workGroup', 'name', fn ($query) => $query->when(self::forOneDepartment(), fn ($query, $department) => $query->where('department_id', $department))->orderBy('sort_order')->orderBy('name'))
                    ->required(fn () => (bool) self::forOneDepartment())
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
                    ->imageSize(40)
                    ->extraImgAttributes(fn (Person $record) => ['style' => 'object-position: '.$record->photoPosition()]),
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
                EditAction::make()->mutateDataUsing(fn (array $data) => self::ownGroup($data)),
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
