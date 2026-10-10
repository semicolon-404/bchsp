<?php

namespace App\Filament\Resources\Announcements;

use App\Filament\Concerns\TranslatesNames;
use App\Filament\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Resources\Announcements\Pages\EditAnnouncement;
use App\Filament\Resources\Announcements\Pages\ListAnnouncements;
use App\Models\Announcement;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * What the hospital tells the public: notices, procurement results, job
 * openings. Each has a type, may carry PDF files, can be pinned to the top,
 * and shows between two dates, so one can be prepared ahead and takes itself
 * down afterwards.
 */
class AnnouncementResource extends Resource
{
    use TranslatesNames;

    protected static ?string $model = Announcement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'Announcements';

    protected static ?string $navigationLabel = 'Announcements';

    protected static ?string $modelLabel = 'announcement';

    protected static ?string $pluralModelLabel = 'announcements';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make(__('Announcement'))
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('title')
                            ->label(__('Title'))
                            ->required()
                            ->maxLength(250),
                        RichEditor::make('body')
                            ->label(__('Details (optional)'))
                            ->helperText(__('Leave blank when the attached file says it all.'))
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('announcements/content')
                            ->fileAttachmentsVisibility('public'),
                        FileUpload::make('attachments')
                            ->label(__('PDF files'))
                            ->multiple()
                            ->maxFiles(Announcement::MAX_FILES)
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(20480)
                            ->disk('public')
                            ->directory('announcements/files')
                            ->visibility('public')
                            // Visitors see the name the file was uploaded under, not the random one it is kept as.
                            ->storeFileNamesIn('attachment_names')
                            ->reorderable()
                            ->downloadable()
                            ->helperText(__('Up to :count PDF files, 20 MB each. Name each file so that a reader knows what it is before opening it.', ['count' => Announcement::MAX_FILES])),
                        Repeater::make('links')
                            ->label(__('Links to documents kept elsewhere'))
                            ->helperText(__('For large files: put them on Google Drive, share them so that anyone with the link can view, and paste the link here. This keeps the website\'s own disk from filling up.'))
                            ->schema([
                                TextInput::make('label')
                                    ->label(__('What the document is'))
                                    ->required()
                                    ->maxLength(200),
                                TextInput::make('url')
                                    ->label(__('Link'))
                                    ->required()
                                    ->url()
                                    ->startsWith('https://')
                                    ->maxLength(1000)
                                    ->placeholder('https://drive.google.com/file/d/…'),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->maxItems(Announcement::MAX_FILES)
                            ->addActionLabel(__('Add a link'))
                            ->reorderableWithButtons(),
                    ]),

                Section::make(__('Showing'))
                    ->columnSpan(1)
                    ->schema([
                        Select::make('announcement_type_id')
                            ->label(__('Type'))
                            ->relationship('type', 'name', fn ($query) => $query->orderBy('sort_order'))
                            ->preload()
                            ->required(),
                        Toggle::make('is_pinned')
                            ->label(__('Pin to the top of the list'))
                            ->helperText(__('Stays above newer announcements until unpinned or ended.')),
                        DateTimePicker::make('starts_at')
                            ->label(__('Show from'))
                            ->default(fn () => now())
                            ->seconds(false)
                            ->required()
                            ->helperText(__('A future date schedules it.')),
                        DateTimePicker::make('ends_at')
                            ->label(__('Show until (optional)'))
                            ->seconds(false)
                            ->after('starts_at')
                            ->helperText(__('Left blank, it stays in the list.')),
                    ]),

                Section::make(__('English version'))
                    ->description(__('Optional. Shown to visitors who switch the website to English; where a field is blank they see the Thai.'))
                    ->columnSpanFull()
                    ->collapsed()
                    ->schema([
                        TextInput::make('title_en')->label(__('Title'))->maxLength(250),
                        RichEditor::make('body_en')
                            ->label(__('Details (optional)'))
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('announcements/content')
                            ->fileAttachmentsVisibility('public'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->inListOrder())
            ->columns([
                IconColumn::make('is_pinned')
                    ->label(__('Pinned'))
                    ->icon(fn (bool $state) => $state ? Heroicon::Bookmark : null)
                    ->color('primary'),
                TextColumn::make('title')
                    ->label(__('Title'))
                    ->searchable()
                    ->wrap(),
                TextColumn::make('type.name')
                    ->label(__('Type'))
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('state')
                    ->label(__('Status'))
                    ->state(fn (Announcement $record) => $record->state())
                    ->formatStateUsing(fn (string $state) => __($state))
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Showing' => 'success',
                        'Scheduled' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('attachments')
                    ->label(__('Files'))
                    ->state(fn (Announcement $record) => (count($record->attachments ?? []) + count($record->documentLinks())) ?: null)
                    ->placeholder('—'),
                TextColumn::make('starts_at')
                    ->label(__('Show from'))
                    ->dateTime('j M Y H:i'),
                TextColumn::make('ends_at')
                    ->label(__('Show until'))
                    ->dateTime('j M Y H:i')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('announcement_type_id')
                    ->label(__('Type'))
                    ->relationship('type', 'name'),
                TernaryFilter::make('is_pinned')
                    ->label(__('Pinned')),
            ])
            ->recordActions([
                Action::make('view')
                    ->label(__('View on the site'))
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Announcement $record) => route('announcements.show', $record))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAnnouncements::route('/'),
            'create' => CreateAnnouncement::route('/create'),
            'edit' => EditAnnouncement::route('/{record}/edit'),
        ];
    }
}
