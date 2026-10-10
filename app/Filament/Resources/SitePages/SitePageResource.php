<?php

namespace App\Filament\Resources\SitePages;

use App\Filament\Concerns\AdminOnly;
use App\Filament\Concerns\TranslatesNames;
use App\Filament\Resources\SitePages\Pages\CreateSitePage;
use App\Filament\Resources\SitePages\Pages\EditSitePage;
use App\Filament\Resources\SitePages\Pages\ListSitePages;
use App\Models\SitePage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Pages of text an administrator writes: the hospital's history, its vision
 * and mission, how to get there. Each has an address of its own under /p/ and
 * can be linked from the footer of every page.
 */
class SitePageResource extends Resource
{
    use AdminOnly, TranslatesNames;

    protected static ?string $model = SitePage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Pages';

    protected static ?string $modelLabel = 'page';

    protected static ?string $pluralModelLabel = 'pages';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        $editor = fn (string $name) => RichEditor::make($name)
            ->fileAttachmentsDisk('public')
            ->fileAttachmentsDirectory('pages')
            ->fileAttachmentsVisibility('public')
            ->maxLength(100000);

        return $schema
            ->columns(3)
            ->components([
                Section::make(__('Page'))
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('title')->label(__('Title'))->required()->maxLength(120),
                        $editor('body')->label(__('Content'))->required(),
                    ]),

                Section::make(__('Publishing'))
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('slug')
                            ->label(__('Web address'))
                            ->prefix('/p/')
                            ->required()
                            ->maxLength(80)
                            ->regex(SitePage::SLUG_PATTERN)
                            ->unique(ignoreRecord: true)
                            ->validationMessages(['regex' => __('Use lowercase English letters, numbers and hyphens only, e.g. "about" or "vision-mission".')])
                            ->helperText(__('Lowercase English, e.g. "about". Changing it later breaks links that were already shared.')),
                        Select::make('section')
                            ->label(__('List it in the menu under'))
                            ->options(array_map('__', SitePage::SECTIONS))
                            ->placeholder(__('Not in the menu'))
                            ->helperText(__('Pages listed in the menu are in the order below, lowest first.')),
                        Toggle::make('is_published')
                            ->label(__('Published'))
                            ->default(true)
                            ->helperText(__('Off: only administrators can open it, to check it first.')),
                        Toggle::make('show_in_footer')
                            ->label(__('Link to it in the footer'))
                            ->default(true),
                        TextInput::make('position')
                            ->label(__('Order'))
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->maxValue(999)
                            ->default(0)
                            ->required()
                            ->helperText(__('Lowest first.')),
                    ]),

                Section::make(__('English version'))
                    ->description(__('Optional. Shown to visitors who switch the website to English; where a field is blank they see the Thai.'))
                    ->columnSpanFull()
                    ->collapsed()
                    ->schema([
                        TextInput::make('title_en')->label(__('Title'))->maxLength(120),
                        $editor('body_en')->label(__('Content')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->columns([
                TextColumn::make('title')
                    ->label(__('Title'))
                    ->searchable()
                    ->description(fn (SitePage $record) => '/p/'.$record->slug),
                TextColumn::make('section')
                    ->label(__('In the menu'))
                    ->formatStateUsing(fn (?string $state) => __(SitePage::SECTIONS[$state] ?? $state))
                    ->badge()
                    ->placeholder('—'),
                IconColumn::make('is_published')
                    ->label(__('Published'))
                    ->boolean(),
                IconColumn::make('show_in_footer')
                    ->label(__('In the footer'))
                    ->boolean(),
                TextColumn::make('position')
                    ->label(__('Order'))
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('Last changed'))
                    ->dateTime('j M Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label(__('View on the site'))
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (SitePage $record) => route('pages.show', $record))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSitePages::route('/'),
            'create' => CreateSitePage::route('/create'),
            'edit' => EditSitePage::route('/{record}/edit'),
        ];
    }
}
