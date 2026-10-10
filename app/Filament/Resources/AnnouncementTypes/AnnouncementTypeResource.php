<?php

namespace App\Filament\Resources\AnnouncementTypes;

use App\Filament\Concerns\AdminOnly;
use App\Filament\Concerns\TranslatesNames;
use App\Filament\Resources\AnnouncementTypes\Pages\ManageAnnouncementTypes;
use App\Models\AnnouncementType;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * The kinds of announcement, for an administrator to arrange. Deleting one
 * leaves its announcements in the list, without a type.
 */
class AnnouncementTypeResource extends Resource
{
    use AdminOnly, TranslatesNames;

    protected static ?string $model = AnnouncementType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Announcements';

    protected static ?string $navigationLabel = 'Types';

    protected static ?string $modelLabel = 'type';

    protected static ?string $pluralModelLabel = 'types';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(80),
                TextInput::make('name_en')
                    ->label(__('Name in English (optional)'))
                    ->maxLength(80),
                TextInput::make('sort_order')
                    ->label(__('Order'))
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->maxValue(999)
                    ->default(0)
                    ->required()
                    ->helperText(__('Lowest first.')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->description(fn (AnnouncementType $record) => $record->name_en),
                TextColumn::make('announcements_count')
                    ->label(__('Announcements'))
                    ->counts('announcements'),
                TextColumn::make('sort_order')
                    ->label(__('Order'))
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
            'index' => ManageAnnouncementTypes::route('/'),
        ];
    }
}
