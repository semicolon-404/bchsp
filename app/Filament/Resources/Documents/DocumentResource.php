<?php

namespace App\Filament\Resources\Documents;

use App\Filament\Concerns\TranslatesNames;
use App\Filament\Resources\Documents\Pages\ManageDocuments;
use App\Models\Document;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * The files offered on the Downloads page: forms, price lists, regulations.
 * Give each a heading to list it under, and drag the rows to put them in order.
 */
class DocumentResource extends Resource
{
    use TranslatesNames;

    protected static ?string $model = Document::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static string|UnitEnum|null $navigationGroup = 'Announcements';

    protected static ?string $navigationLabel = 'Downloads';

    protected static ?string $modelLabel = 'document';

    protected static ?string $pluralModelLabel = 'documents';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label(__('Name of the document'))
                    ->required()
                    ->maxLength(250)
                    ->columnSpanFull(),
                TextInput::make('title_en')
                    ->label(__('Name in English (optional)'))
                    ->maxLength(250)
                    ->columnSpanFull(),
                Select::make('section')
                    ->label(__('Page it is offered on'))
                    ->options(array_map('__', Document::SECTIONS))
                    ->default('downloads')
                    ->required()
                    ->selectablePlaceholder(false)
                    ->live(),
                TextInput::make('year')
                    ->label(__('Fiscal year'))
                    ->numeric()
                    ->integer()
                    ->minValue(2550)
                    ->maxValue(2700)
                    ->placeholder((string) (now()->year + 543))
                    ->required(fn (Get $get) => $get('section') === 'ita')
                    ->visible(fn (Get $get) => $get('section') === 'ita')
                    ->helperText(__('The Buddhist year the document is filed under, e.g. 2569.')),
                TextInput::make('group')
                    ->label(__('Heading to list it under (optional)'))
                    ->maxLength(100)
                    // The headings already in use are offered, so that one is not spelt two ways.
                    ->datalist(fn () => Document::query()->whereNotNull('group')->distinct()->orderBy('group')->pluck('group')->all())
                    ->placeholder(__('Forms'))
                    ->helperText(__('On the ITA page, the item it belongs to, e.g. "MOIT 1".'))
                    ->columnSpanFull(),
                FileUpload::make('file_path')
                    ->label(__('File'))
                    ->required()
                    ->acceptedFileTypes(array_keys(Document::TYPES))
                    ->maxSize(30720)
                    ->disk('public')
                    ->directory('documents')
                    ->visibility('public')
                    ->storeFileNamesIn('file_name')
                    ->downloadable()
                    ->helperText(__('PDF, Word (.docx), Excel (.xlsx) or PowerPoint (.pptx), up to 30 MB. Files with macros are not accepted.'))
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
                TextColumn::make('title')
                    ->label(__('Name of the document'))
                    ->searchable()
                    ->wrap()
                    ->description(fn (Document $record) => $record->file_name),
                TextColumn::make('section')
                    ->label(__('Page'))
                    ->formatStateUsing(fn (string $state, Document $record) => __(Document::SECTIONS[$state] ?? $state).($record->year ? ' '.$record->year : ''))
                    ->badge(),
                TextColumn::make('group')
                    ->label(__('Heading'))
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),
                TextColumn::make('kind')
                    ->label(__('File'))
                    ->state(fn (Document $record) => $record->kind()),
                ToggleColumn::make('is_active')
                    ->label(__('Shown on the site')),
            ])
            ->filters([
                SelectFilter::make('section')
                    ->label(__('Page'))
                    ->options(array_map('__', Document::SECTIONS)),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDocuments::route('/'),
        ];
    }
}
