<?php

namespace App\Filament\Resources\SitePopups;

use App\Filament\Concerns\NotForDepartments;
use App\Filament\Resources\SitePopups\Pages\ManageSitePopups;
use App\Models\SitePopup;
use App\Support\OptimizedUpload;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Popups set up on their own (App\Models\SitePopup), apart from the announcements: whatever the hospital wants to
 * put in front of visitors, as it wants it. Whole hospital: the administrators. Drag the rows to order several.
 */
class SitePopupResource extends Resource
{
    use NotForDepartments;

    protected static ?string $model = SitePopup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWindow;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationLabel(): string
    {
        return __('Popups');
    }

    public static function getModelLabel(): string
    {
        return __('popup');
    }

    public static function getPluralModelLabel(): string
    {
        return __('popups');
    }

    public static function form(Schema $schema): Schema
    {
        $link = fn (string $field) => TextInput::make($field)
            ->label(__('Goes to'))
            ->maxLength(500)
            ->placeholder('https://… '.__('or').' /schedule')
            ->rule('regex:~^(https?://[^\s"\'<>]+|/[^\s"\'<>]*)$~i');

        return $schema
            ->columns(2)
            ->components([
                Section::make(__('What it says'))
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        OptimizedUpload::on(FileUpload::make('image_path'))
                            ->label(__('Picture (optional)'))
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->disk('public')
                            ->directory('popups')
                            ->visibility('public')
                            ->maxSize(8192)
                            ->columnSpanFull(),
                        TextInput::make('title')->label(__('Title'))->required()->maxLength(160)->columnSpanFull(),
                        // Set out as wished: bold, colour, lists, links, alignment, headings.
                        RichEditor::make('body')
                            ->label(__('Text (optional)'))
                            ->toolbarButtons([['bold', 'italic', 'underline', 'strike', 'textColor', 'link'], ['h2', 'h3', 'alignStart', 'alignCenter', 'alignEnd'], ['bulletList', 'orderedList', 'blockquote'], ['undo', 'redo']])
                            ->columnSpanFull(),
                        TextInput::make('button_label')->label(__('Button'))->maxLength(60)->placeholder(__('e.g. Register here')),
                        $link('button_url')->requiredWith('button_label'),
                        TextInput::make('second_label')->label(__('Second button (optional)'))->maxLength(60),
                        $link('second_url')->requiredWith('second_label'),
                    ]),
                Section::make(__('How it looks'))
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Radio::make('size')->label(__('Size'))->options(collect(SitePopup::SIZES)->map(fn ($l) => __($l))->all())->default('medium')->inline(),
                        Radio::make('placement')->label(__('Where on the screen'))->options(collect(SitePopup::PLACEMENTS)->map(fn ($l) => __($l))->all())->default('center'),
                        ColorPicker::make('accent')->label(__('Colour (optional)'))->helperText(__('Of its button and band. Left empty, the site\'s own.')),
                    ]),
                Section::make(__('When and where'))
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Radio::make('pages')->label(__('Shown on'))->options(collect(SitePopup::PAGES)->map(fn ($l) => __($l))->all())->default('home'),
                        Radio::make('frequency')->label(__('How often a visitor sees it'))->options(collect(SitePopup::FREQUENCIES)->map(fn ($l) => __($l))->all())->default('once'),
                        DateTimePicker::make('starts_at')->label(__('From (optional)'))->native(false)->seconds(false),
                        DateTimePicker::make('ends_at')->label(__('Until (optional)'))->native(false)->seconds(false)->after('starts_at'),
                        Toggle::make('is_active')->label(__('On'))->default(true)->inline(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->emptyStateHeading(__('No popup yet'))
            ->emptyStateDescription(__('A picture, a few words and a button, in front of visitors for as long as you choose: apart from the announcements.'))
            ->columns([
                ImageColumn::make('image_path')->label('')->disk('public')->imageHeight(44),
                TextColumn::make('title')->label(__('Title'))->searchable()->weight('semibold')->wrap(),
                TextColumn::make('pages')->label(__('Shown on'))->formatStateUsing(fn (string $state) => __(SitePopup::PAGES[$state] ?? $state))->badge()->color('gray'),
                TextColumn::make('ends_at')->label(__('Until'))->dateTime('j M Y H:i')->placeholder('—'),
                ToggleColumn::make('is_active')->label(__('On')),
            ])
            ->recordActions([
                Action::make('preview')
                    ->label(__('Preview'))
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->url(fn (SitePopup $record) => route('home', ['popup' => $record->id]))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageSitePopups::route('/')];
    }
}
