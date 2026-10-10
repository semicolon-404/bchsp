<?php

namespace App\Filament\Resources\Departments;

use App\Filament\Concerns\AdminOnly;
use App\Filament\Concerns\TranslatesNames;
use App\Filament\Resources\Departments\Pages\ManageDepartments;
use App\Models\Department;
use App\Support\OptimizedUpload;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
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
 * The units of the hospital shown on the site: what each does, where it is,
 * its phone number and its hours. Drag the rows to put them in order.
 */
class DepartmentResource extends Resource
{
    use AdminOnly, TranslatesNames;

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
                    ->maxLength(150),
                TextInput::make('name_en')
                    ->label(__('Name in English (optional)'))
                    ->maxLength(150),
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
                TextInput::make('location')
                    ->label(__('Location'))
                    ->maxLength(200)
                    ->placeholder(__('Building 1, ground floor')),
                TextInput::make('phone')
                    ->label(__('Phone'))
                    ->maxLength(100)
                    ->placeholder('044-579076 ต่อ 101'),
                Textarea::make('hours')
                    ->label(__('Opening hours'))
                    ->rows(2)
                    ->maxLength(500)
                    ->placeholder(__('Every day, 08.00–16.00'))
                    ->columnSpanFull(),
                OptimizedUpload::on(FileUpload::make('image_path'))
                    ->label(__('Picture (optional)'))
                    ->image()
                    // No SVG: it can carry a script.
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->disk('public')
                    ->directory('departments')
                    ->visibility('public')
                    ->maxSize(8192)
                    // Staff and patients may be in a photo of a ward.
                    ->helperText(__('A picture of the place. Do not use one in which a patient can be recognised.'))
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label(__('Shown on the site'))
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
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
            'index' => ManageDepartments::route('/'),
        ];
    }
}
