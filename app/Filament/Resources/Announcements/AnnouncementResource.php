<?php

namespace App\Filament\Resources\Announcements;

use App\Filament\Concerns\TranslatesNames;
use App\Filament\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Resources\Announcements\Pages\EditAnnouncement;
use App\Filament\Resources\Announcements\Pages\ListAnnouncements;
use App\Models\Announcement;
use App\Support\OptimizedUpload;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * What the hospital tells the public, and the one place staff post it: notices,
 * procurement results, job openings, posters, health knowledge, activities. Each has a type, may carry PDF files, can be pinned to the top,
 * and shows between two dates, so one can be prepared ahead and takes itself
 * down afterwards.
 */
class AnnouncementResource extends Resource
{
    use TranslatesNames;

    protected static ?string $model = Announcement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'Announcements';

    protected static ?string $navigationLabel = 'Post an announcement or poster';

    protected static ?string $modelLabel = 'announcement';

    protected static ?string $pluralModelLabel = 'announcements';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    /** The account of one department sees and changes what that department put out, and nothing else. */
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getEloquentQuery()->when($user?->postsForOneDepartment(), fn (Builder $query) => $query->where('department_id', $user->department_id));
    }

    /** Whether whoever is signed in posts for the whole hospital: an administrator, or an author with no department. */
    public static function forTheWholeHospital(): bool
    {
        return ! auth()->user()?->postsForOneDepartment();
    }

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
                        OptimizedUpload::on(FileUpload::make('image_path'))
                            ->label(__('Cover or picture of the announcement (optional)'))
                            ->image()
                            // No SVG: it can carry a script.
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->disk('public')
                            ->directory('announcements/pictures')
                            ->visibility('public')
                            ->maxSize(8192)
                            ->helperText(__('For an announcement made as one picture ("one page"), upright or wide: it is shown whole on the announcement\'s page, and is its cover in the list. Up to 8 MB; it is made smaller by itself.')),
                        Toggle::make('in_slider')
                            ->label(__('Also show this picture among the slides of the home page'))
                            ->helperText(__('It leads to this announcement, and leaves the slides when the announcement ends. A wide picture suits the slides best.')),
                        Toggle::make('is_popup')
                            ->visible(fn () => self::forTheWholeHospital())
                            ->label(__('Open this picture over the page when the site is first opened'))
                            ->helperText(__('One at a time: when several are marked, the pinned or newest one opens. A visitor who closes it is not shown it again.')),
                        OptimizedUpload::on(FileUpload::make('gallery'))
                            ->label(__('Album of photographs (optional)'))
                            ->image()
                            ->multiple()
                            ->maxFiles(Announcement::MAX_PHOTOS)
                            // No SVG: it can carry a script.
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->disk('public')
                            ->directory('announcements/albums')
                            ->visibility('public')
                            ->maxSize(8192)
                            ->reorderable()
                            ->panelLayout('grid')
                            ->helperText(__('The photographs of an activity, shown as an album under the text. Up to :count, 8 MB each; they are made smaller by themselves. Do not use one in which a patient can be recognised.', ['count' => Announcement::MAX_PHOTOS])),
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
                        // What the reviewer wrote when sending it back: read by the department, changed by nobody here.
                        Textarea::make('review_note')
                            ->label(__('The reviewer\'s note'))
                            ->rows(3)
                            ->disabled()
                            ->visible(fn (?Announcement $record) => filled($record?->review_note)),
                        Select::make('status')
                            ->label(__('Status'))
                            // The account of a department cannot put its own writing out while the hospital asks to see it first.
                            ->options(fn () => Announcement::waitsForApproval(auth()->user())
                                ? [Announcement::STATUS_DRAFT => __('Draft'), Announcement::STATUS_PENDING => __('Send for approval')]
                                : [Announcement::STATUS_DRAFT => __('Draft'), Announcement::STATUS_PUBLISHED => __('Published')])
                            ->default(fn () => Announcement::waitsForApproval(auth()->user()) ? Announcement::STATUS_PENDING : Announcement::STATUS_PUBLISHED)
                            ->required()
                            ->selectablePlaceholder(false)
                            ->helperText(fn () => Announcement::waitsForApproval(auth()->user())
                                ? __('It is shown to the public after it has been approved. Every change is looked at again.')
                                : __('A draft is seen by staff only, whatever its dates.')),
                        Select::make('department_id')
                            ->label(__('Department (optional)'))
                            ->relationship('department', 'name', fn ($query) => $query->orderBy('sort_order')->orderBy('name'))
                            ->preload()
                            ->searchable()
                            ->live()
                            // The account of a department posts for that department: there is nothing to choose (set in the pages).
                            ->visible(fn () => self::forTheWholeHospital())
                            ->helperText(__('Also shown on the board of this department, on its own page.')),
                        Toggle::make('pinned_in_department')
                            ->label(__('Pin to the top of the department\'s page'))
                            ->helperText(__('One at a time: pinning this lets go of the one pinned before.'))
                            ->visible(fn (Get $get) => ! self::forTheWholeHospital() || filled($get('department_id')))
                            ->live(),
                        // What takes a place of the whole hospital's is for those who post for the whole hospital.
                        Toggle::make('is_pinned')
                            ->visible(fn () => self::forTheWholeHospital())
                            ->label(__('Pin to the top of the list'))
                            ->helperText(__('Stays above newer announcements until unpinned or ended.')),
                        DatePicker::make('event_on')
                            ->label(__('Day it happens (optional)'))
                            ->helperText(__('For something with a day of its own: a mobile unit, a blood drive, a clinic closed. It is then on the calendar. Not the day the announcement is shown.')),
                        DatePicker::make('event_until')
                            ->label(__('Until (optional)'))
                            ->afterOrEqual('event_on')
                            ->helperText(__('Only when it runs over several days.')),
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
                            ->helperText(__('After this it leaves the home page and the top of the list, and stays in the archive. Left blank, it does not end.')),
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
                    ->wrap()
                    // Wide enough to be read as a title, not a column of single words.
                    ->extraCellAttributes(['style' => 'min-width: 20rem'])
                    ->weight('medium'),
                TextColumn::make('type.name')
                    ->label(__('Type'))
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('department.name')
                    ->label(__('Department'))
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('state')
                    ->label(__('Status'))
                    ->state(fn (Announcement $record) => $record->state())
                    ->formatStateUsing(fn (string $state) => __($state))
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Showing' => 'success',
                        'Scheduled' => 'warning',
                        'Waiting for approval', 'Sent back' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('views')
                    ->label(__('Times opened'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('helpful')
                    ->label(__('Found useful'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('fiscal_year')
                    ->label(__('Fiscal year'))
                    ->state(fn (Announcement $record) => Announcement::fiscalYearOf($record->starts_at ?? $record->created_at)),
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
                SelectFilter::make('department_id')
                    ->visible(fn () => self::forTheWholeHospital())
                    ->label(__('Department'))
                    ->relationship('department', 'name'),
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options([Announcement::STATUS_PUBLISHED => __('Published'), Announcement::STATUS_PENDING => __('Waiting for approval'), Announcement::STATUS_RETURNED => __('Sent back'), Announcement::STATUS_DRAFT => __('Draft')]),
                SelectFilter::make('fiscal_year')
                    ->label(__('Fiscal year'))
                    ->options(fn () => collect(Announcement::query()->pluck('starts_at')->map(fn ($date) => Announcement::fiscalYearOf($date))->unique()->sortDesc()->values())->mapWithKeys(fn ($year) => [$year => $year])->all())
                    ->query(fn ($query, array $data) => filled($data['value'] ?? null) ? $query->ofFiscalYear((int) $data['value']) : $query),
                TernaryFilter::make('is_pinned')
                    ->label(__('Pinned')),
            ])
            ->recordActions([
                // For those who post for the whole hospital: let what a department wrote out.
                Action::make('approve')
                    ->label(__('Approve'))
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (Announcement $record) => $record->status === Announcement::STATUS_PENDING && self::forTheWholeHospital())
                    ->requiresConfirmation()
                    ->modalDescription(__('It will be shown to the public from its "Show from" date.'))
                    ->action(fn (Announcement $record) => $record->update(['status' => Announcement::STATUS_PUBLISHED, 'review_note' => null])),
                // Or send it back, saying what should change: the department reads the note on the post itself.
                Action::make('sendBack')
                    ->label(__('Send back'))
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('warning')
                    ->visible(fn (Announcement $record) => $record->status === Announcement::STATUS_PENDING && self::forTheWholeHospital())
                    ->schema([
                        Textarea::make('review_note')
                            ->label(__('What should be changed'))
                            ->helperText(__('The department reads this on the post. Say what to change, plainly.'))
                            ->rows(4)
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(fn (Announcement $record, array $data) => $record->update(['status' => Announcement::STATUS_RETURNED, 'review_note' => $data['review_note']])),
                Action::make('view')
                    ->label(__('View on the site'))
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Announcement $record) => route('announcements.show', $record))
                    ->openUrlInNewTab(),
                EditAction::make(),
                // For what comes round again (the month's procurement notice): a draft with the same words, to change
                // and put out. Its files are not copied: they belong to the first one, and go when that one goes.
                ReplicateAction::make()
                    ->label(__('Make a copy'))
                    ->excludeAttributes(['slug', 'views', 'helpful', 'image_path', 'gallery', 'attachments', 'attachment_names', 'review_note'])
                    ->beforeReplicaSaved(function (Announcement $replica) {
                        $replica->fill([
                            'title' => Str::limit($replica->title, 235, '').' '.__('(copy)'),
                            'status' => Announcement::STATUS_DRAFT,
                            'author_id' => auth()->id(),
                            'is_pinned' => false,
                            'pinned_in_department' => false,
                            'in_slider' => false,
                            'is_popup' => false,
                            'starts_at' => now(),
                            'ends_at' => null,
                        ]);
                    })
                    ->successNotificationTitle(__('Copied as a draft. Pictures and files are not copied.'))
                    ->successRedirectUrl(fn (Announcement $replica) => self::getUrl('edit', ['record' => $replica])),
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
