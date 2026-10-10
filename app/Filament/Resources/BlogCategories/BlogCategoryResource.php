<?php

namespace App\Filament\Resources\BlogCategories;

use App\Filament\Concerns\TranslatesNames;
use App\Filament\Resources\BlogCategories\Pages\ManageBlogCategories;
use App\Models\BlogCategory;
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
 * What the articles are sorted into. Deleting one leaves its articles in
 * place, without a category.
 */
class BlogCategoryResource extends Resource
{
    use TranslatesNames;

    protected static ?string $model = BlogCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Blog';

    protected static ?string $navigationLabel = 'Categories';

    protected static ?string $modelLabel = 'category';

    protected static ?string $pluralModelLabel = 'categories';

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
                    ->description(fn (BlogCategory $record) => $record->name_en),
                TextColumn::make('posts_count')
                    ->label(__('Articles'))
                    ->counts('posts'),
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
            'index' => ManageBlogCategories::route('/'),
        ];
    }
}
