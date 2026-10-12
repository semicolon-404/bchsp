<?php

namespace App\Filament\Resources\Departments;

use App\Filament\Concerns\TranslatesNames;
use App\Filament\Resources\Departments\Pages\ManageDepartments;
use App\Models\Department;
use App\Support\OptimizedUpload;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * The units of the hospital shown on the site: what each does, where it is,
 * its phone number and its hours. Drag the rows to put them in order.
 */
class DepartmentResource extends Resource
{
    use TranslatesNames;

    /** An administrator keeps them all; the account of a department keeps the page of its own. */
    public static function canAccess(): bool
    {
        return (bool) (auth()->user()?->isAdmin() || auth()->user()?->postsForOneDepartment());
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getEloquentQuery()->when(! $user?->isAdmin(), fn (Builder $query) => $query->whereKey($user?->department_id));
    }

    /** Adding, removing, ordering, renaming and hiding departments is the administrator's. */
    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function canDelete(Model $record): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function canReorder(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function getNavigationLabel(): string
    {
        return __(auth()->user()?->isAdmin() ? 'Departments' : 'Our department\'s page');
    }

    protected static ?string $model = Department::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Hospital';

    protected static ?string $navigationLabel = 'Departments';

    protected static ?string $modelLabel = 'department';

    protected static ?string $pluralModelLabel = 'departments';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(150)
                    ->disabled(fn () => ! auth()->user()->isAdmin()),
                TextInput::make('name_en')
                    ->label(__('Name in English (optional)'))
                    ->maxLength(150)
                    ->disabled(fn () => ! auth()->user()->isAdmin()),
                TextInput::make('headline')
                    ->label(__('Line under the name (optional)'))
                    ->maxLength(200)
                    ->placeholder(__('Care for teeth and gums, for every age')),
                TextInput::make('headline_en')
                    ->label(__('Line under the name, in English (optional)'))
                    ->maxLength(200),
                Textarea::make('description')
                    ->label(__('What it does'))
                    ->rows(4)
                    ->maxLength(5000)
                    ->columnSpanFull(),
                Textarea::make('description_en')
                    ->label(__('What it does, in English (optional)'))
                    ->rows(3)
                    ->maxLength(5000)
                    ->columnSpanFull(),
                Textarea::make('services')
                    ->label(__('Services (optional)'))
                    ->rows(5)
                    ->maxLength(3000)
                    ->placeholder(__("Fillings and extractions\nScaling\nDentures"))
                    ->helperText(__('One service to a line. Shown as a list on the department\'s page.'))
                    ->columnSpanFull(),
                TextInput::make('location')
                    ->label(__('Location'))
                    ->maxLength(200)
                    ->placeholder(__('Building 1, ground floor')),
                TextInput::make('phone')
                    ->label(__('Phone'))
                    ->maxLength(100)
                    ->placeholder('044-579076 ต่อ 101'),
                TextInput::make('email')
                    ->label(__('Email (optional)'))
                    ->email()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Textarea::make('hours')
                    ->label(__('Opening hours'))
                    ->rows(2)
                    ->maxLength(500)
                    ->placeholder(__('Every day, 08.00–16.00'))
                    ->columnSpanFull(),
                TextInput::make('facebook_url')
                    ->label(__('Facebook page (optional)'))
                    ->url()
                    ->startsWith('https://')
                    ->maxLength(500)
                    ->placeholder('https://www.facebook.com/…'),
                TextInput::make('line_url')
                    ->label(__('LINE (optional)'))
                    ->url()
                    ->startsWith('https://')
                    ->maxLength(500)
                    ->placeholder('https://line.me/…'),
                OptimizedUpload::on(FileUpload::make('avatar_path'))
                    ->label(__('Round picture of the department (optional)'))
                    ->image()
                    ->avatar()
                    // No SVG: it can carry a script.
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->disk('public')
                    ->directory('departments/avatars')
                    ->visibility('public')
                    ->maxSize(4096)
                    ->helperText(__('A square picture: an emblem, or the team. Shown round, beside the name and on every post.'))
                    ->columnSpanFull(),
                OptimizedUpload::on(FileUpload::make('image_path'))
                    ->label(__('Cover across the top of the page (optional)'))
                    ->image()
                    // No SVG: it can carry a script.
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->disk('public')
                    ->directory('departments')
                    ->visibility('public')
                    ->maxSize(8192)
                    // Staff and patients may be in a photo of a ward.
                    ->helperText(__('A wide picture of the place, about three times as wide as it is tall. Do not use one in which a patient can be recognised.'))
                    ->columnSpanFull(),
                Repeater::make('people')
                    ->label(__('People who work here (optional)'))
                    ->helperText(__('Shown on the "People" tab of the department\'s page, in this order. Up to :count. Ask each person before putting their name and photograph on the website.', ['count' => Department::MAX_PEOPLE]))
                    ->schema([
                        TextInput::make('name')->label(__('Name'))->required()->maxLength(150),
                        TextInput::make('position')->label(__('Position'))->maxLength(150),
                        OptimizedUpload::on(FileUpload::make('photo'))
                            ->label(__('Photograph (optional)'))
                            ->image()
                            ->avatar()
                            // No SVG: it can carry a script.
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->disk('public')
                            ->directory('departments/people')
                            ->visibility('public')
                            ->maxSize(4096),
                    ])
                    ->columns(3)
                    ->defaultItems(0)
                    ->maxItems(Department::MAX_PEOPLE)
                    ->addActionLabel(__('Add a person'))
                    ->reorderableWithButtons()
                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                    ->collapsible()
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label(__('Shown on the site'))
                    ->default(true)
                    ->visible(fn () => auth()->user()->isAdmin()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order', fn () => self::canCreate())
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->description(fn (Department $record) => $record->location),
                TextColumn::make('phone')
                    ->label(__('Phone'))
                    ->placeholder('—'),
                TextColumn::make('hours')
                    ->label(__('Opening hours'))
                    ->limit(40)
                    ->placeholder('—'),
                ToggleColumn::make('is_active')
                    ->label(__('Shown on the site'))
                    ->disabled(fn () => ! auth()->user()->isAdmin()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->visible(fn () => self::canCreate()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDepartments::route('/'),
        ];
    }
}
