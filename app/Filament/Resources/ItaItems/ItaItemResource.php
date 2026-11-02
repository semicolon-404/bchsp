<?php

namespace App\Filament\Resources\ItaItems;

use App\Filament\Concerns\NotForDepartments;
use App\Filament\Concerns\TranslatesNames;
use App\Filament\Resources\ItaItems\Pages\CreateItaItem;
use App\Filament\Resources\ItaItems\Pages\EditItaItem;
use App\Filament\Resources\ItaItems\Pages\ListItaItems;
use App\Models\ItaItem;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * The Integrity and Transparency Assessment: for each fiscal year, its
 * numbered items and the evidence published under each. The list opens on the
 * newest year; "Start a new year" copies a year's headings to the next.
 */
class ItaItemResource extends Resource
{
    use NotForDepartments, TranslatesNames;

    protected static ?string $model = ItaItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'ITA';

    protected static ?string $navigationLabel = 'ITA items';

    protected static ?string $modelLabel = 'ITA item';

    protected static ?string $pluralModelLabel = 'ITA items';

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make(__('Item'))
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('year')
                            ->label(__('Fiscal year'))
                            ->numeric()
                            ->integer()
                            ->minValue(2550)
                            ->maxValue(2700)
                            ->default(fn () => ItaItem::max('year') ?? now()->year + 543)
                            ->required()
                            ->helperText(__('The Buddhist year, e.g. 2569.')),
                        TextInput::make('number')
                            ->label(__('Number'))
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(999)
                            ->required()
                            ->helperText(__('Its place in the year\'s list.')),
                        TextInput::make('code')
                            ->label(__('What it is called'))
                            ->maxLength(30)
                            ->placeholder('MOIT 1')
                            ->helperText(__('Left blank, it is called "MOIT" and its number.')),
                        TextInput::make('category')
                            ->label(__('Heading it sits under'))
                            ->maxLength(200)
                            ->placeholder('1. การเปิดเผยข้อมูล')
                            // The headings already used, to pick from rather than type again slightly differently.
                            ->datalist(fn () => ItaItem::query()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category')->all())
                            ->helperText(__('Items with the same heading are shown together.')),
                        Toggle::make('is_active')
                            ->label(__('Shown on the site'))
                            ->default(true),
                    ]),

                Section::make(__('What is published'))
                    ->columnSpan(2)
                    ->schema([
                        Textarea::make('title')
                            ->label(__('Heading of the item'))
                            ->required()
                            ->rows(2)
                            ->maxLength(500),
                        Textarea::make('description')
                            ->label(__('Note (optional)'))
                            ->rows(2)
                            ->maxLength(2000),
                        Repeater::make('parts')
                            ->label(__('Parts of the item'))
                            ->helperText(__('The numbered documents the assessment asks for under this item, each in the round it is due, with its own files or links.'))
                            ->schema([
                                Select::make('round')
                                    ->label(__('Due in'))
                                    ->options(collect(ItaItem::ROUNDS)->map(fn (string $label) => __($label))->all())
                                    ->default('year')
                                    ->required()
                                    ->native(false),
                                Textarea::make('title')
                                    ->label(__('What is asked for'))
                                    ->required()
                                    ->rows(2)
                                    ->maxLength(1000)
                                    ->placeholder('1.1 บันทึกข้อความที่ผู้บริหารลงนาม…'),
                                FileUpload::make('attachments')
                                    ->label(__('PDF files'))
                                    ->multiple()
                                    ->maxFiles(ItaItem::MAX_FILES)
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->maxSize(20480)
                                    ->disk('public')
                                    ->directory('ita')
                                    ->visibility('public')
                                    ->storeFileNamesIn('attachment_names')
                                    ->reorderable()
                                    ->downloadable(),
                                Repeater::make('links')
                                    ->label(__('Links to documents kept elsewhere'))
                                    ->schema([
                                        TextInput::make('label')->label(__('What the document is'))->required()->maxLength(200),
                                        TextInput::make('url')->label(__('Link'))->required()->url()->startsWith('https://')->maxLength(1000)->placeholder('https://drive.google.com/file/d/…'),
                                    ])
                                    ->columns(2)
                                    ->defaultItems(0)
                                    ->maxItems(ItaItem::MAX_FILES)
                                    ->addActionLabel(__('Add a link')),
                            ])
                            ->itemLabel(fn (array $state) => Str::limit(trim((string) ($state['title'] ?? '')), 90) ?: null)
                            ->collapsible()
                            ->collapsed()
                            ->defaultItems(0)
                            ->reorderableWithButtons()
                            ->addActionLabel(__('Add a part')),
                        FileUpload::make('attachments')
                            ->label(__('PDF files of the item itself'))
                            ->multiple()
                            ->maxFiles(ItaItem::MAX_FILES)
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(20480)
                            ->disk('public')
                            ->directory('ita')
                            ->visibility('public')
                            // Visitors see the name the file was uploaded under, not the random one it is kept as.
                            ->storeFileNamesIn('attachment_names')
                            ->reorderable()
                            ->downloadable()
                            ->helperText(__('Up to :count PDF files, 20 MB each. Name each file so that a reader knows what it is before opening it.', ['count' => ItaItem::MAX_FILES])),
                        Repeater::make('links')
                            ->label(__('Links to documents kept elsewhere'))
                            ->helperText(__('For large files: put them on Google Drive, share them so that anyone with the link can view, and paste the link here. This keeps the website\'s own disk from filling up.'))
                            ->schema([
                                TextInput::make('label')->label(__('What the document is'))->required()->maxLength(200),
                                TextInput::make('url')->label(__('Link'))->required()->url()->startsWith('https://')->maxLength(1000)->placeholder('https://drive.google.com/file/d/…'),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->maxItems(ItaItem::MAX_FILES)
                            ->addActionLabel(__('Add a link'))
                            ->reorderableWithButtons(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->orderByDesc('year')->orderBy('number'))
            ->columns([
                TextColumn::make('year')
                    ->label(__('Fiscal year'))
                    ->badge()
                    ->color('gray'),
                TextColumn::make('code')
                    ->label(__('Item'))
                    ->searchable(),
                TextColumn::make('title')
                    ->label(__('Heading of the item'))
                    ->searchable()
                    ->wrap()
                    ->limit(120),
                TextColumn::make('evidence')
                    ->label(__('Documents'))
                    ->state(fn (ItaItem $record) => $record->progress()['done'].' / '.$record->progress()['of'])
                    ->badge()
                    ->color(fn (ItaItem $record) => match (true) {
                        $record->progress()['done'] === $record->progress()['of'] => 'success',
                        $record->progress()['done'] > 0 => 'warning',
                        default => 'danger',
                    }),
                ToggleColumn::make('is_active')
                    ->label(__('Shown on the site')),
            ])
            ->filters([
                SelectFilter::make('year')
                    ->label(__('Fiscal year'))
                    ->options(fn () => ItaItem::query()->distinct()->orderByDesc('year')->pluck('year', 'year')->all())
                    // The list opens on the newest year: nobody wants five years in one table.
                    ->default(fn () => ItaItem::max('year')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListItaItems::route('/'),
            'create' => CreateItaItem::route('/create'),
            'edit' => EditItaItem::route('/{record}/edit'),
        ];
    }
}
