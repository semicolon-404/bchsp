<?php

namespace App\Filament\Resources\BlogPosts;

use App\Filament\Concerns\TranslatesNames;
use App\Filament\Resources\BlogPosts\Pages\CreateBlogPost;
use App\Filament\Resources\BlogPosts\Pages\EditBlogPost;
use App\Filament\Resources\BlogPosts\Pages\ListBlogPosts;
use App\Models\BlogPost;
use App\Support\OptimizedUpload;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * The articles of the blog: health knowledge and news of activities. An
 * article is a draft until it is published; a publish date in the future
 * schedules it, and it comes out by itself when that time arrives.
 */
class BlogPostResource extends Resource
{
    use TranslatesNames;

    protected static ?string $model = BlogPost::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = 'Blog';

    protected static ?string $navigationLabel = 'Articles';

    protected static ?string $modelLabel = 'article';

    protected static ?string $pluralModelLabel = 'articles';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    private const STATUSES = [
        BlogPost::STATUS_DRAFT => 'Draft',
        BlogPost::STATUS_PUBLISHED => 'Published',
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make(__('Article'))
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('title')
                            ->label(__('Title'))
                            ->required()
                            ->maxLength(180),
                        Textarea::make('excerpt')
                            ->label(__('Summary'))
                            ->rows(2)
                            ->maxLength(300)
                            ->helperText(__('One or two sentences shown on the list of articles and in link previews.')),
                        RichEditor::make('body')
                            ->label(__('Content'))
                            ->required()
                            // Pictures dropped into the editor are stored alongside the rest of the site's uploads.
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('blog/content')
                            ->fileAttachmentsVisibility('public'),
                        OptimizedUpload::on(FileUpload::make('gallery'))
                            ->label(__('Album of photographs (optional)'))
                            ->image()
                            ->multiple()
                            ->maxFiles(BlogPost::MAX_PHOTOS)
                            // No SVG: it can carry a script.
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->disk('public')
                            ->directory('blog/albums')
                            ->visibility('public')
                            ->maxSize(8192)
                            ->reorderable()
                            ->panelLayout('grid')
                            ->helperText(__('The photographs of an activity, shown as an album under the article. Up to :count, 8 MB each; they are made smaller by themselves. Do not use one in which a patient can be recognised.', ['count' => BlogPost::MAX_PHOTOS])),
                    ]),

                Section::make(__('Publishing'))
                    ->columnSpan(1)
                    ->schema([
                        Select::make('status')
                            ->label(__('Status'))
                            ->options(array_map('__', self::STATUSES))
                            ->default(BlogPost::STATUS_DRAFT)
                            ->required()
                            ->selectablePlaceholder(false),
                        DateTimePicker::make('published_at')
                            ->label(__('Publish date'))
                            ->seconds(false)
                            ->helperText(__('Blank = as soon as it is published. A future date schedules it.')),
                        Select::make('blog_category_id')
                            ->label(__('Category'))
                            ->relationship('category', 'name')
                            ->preload()
                            ->searchable(),
                        OptimizedUpload::on(FileUpload::make('cover_path'))
                            ->label(__('Cover image'))
                            ->image()
                            // No SVG: it can carry a script.
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->disk('public')
                            ->directory('blog/covers')
                            ->visibility('public')
                            ->maxSize(8192)
                            ->helperText(__('Wide pictures work best (about 1600 × 900). It is made smaller by itself.')),
                        TextInput::make('slug')
                            ->label(__('Web address'))
                            ->maxLength(150)
                            ->prefix('/blog/')
                            ->helperText(__('Leave blank to build it from the title. Changing it later breaks links that were already shared.')),
                    ]),

                Section::make(__('English version'))
                    ->description(__('Optional. Shown to visitors who switch the website to English; where a field is blank they see the Thai.'))
                    ->columnSpanFull()
                    ->collapsed()
                    ->schema([
                        TextInput::make('title_en')
                            ->label(__('Title'))
                            ->maxLength(180),
                        Textarea::make('excerpt_en')
                            ->label(__('Summary'))
                            ->rows(2)
                            ->maxLength(300),
                        RichEditor::make('body_en')
                            ->label(__('Content'))
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('blog/content')
                            ->fileAttachmentsVisibility('public'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('cover_path')
                    ->label('')
                    ->disk('public')
                    ->imageWidth(96)
                    ->imageHeight(54),
                TextColumn::make('title')
                    ->label(__('Title'))
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('category.name')
                    ->label(__('Category'))
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->formatStateUsing(fn (BlogPost $record) => __($record->statusLabel()))
                    ->color(fn (BlogPost $record) => match ($record->statusLabel()) {
                        'Draft' => 'gray',
                        'Scheduled' => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('author.name')
                    ->label(__('Written by'))
                    ->placeholder('—'),
                TextColumn::make('published_at')
                    ->label(__('Publish date'))
                    ->dateTime('j M Y H:i')
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(array_map('__', self::STATUSES)),
                SelectFilter::make('blog_category_id')
                    ->label(__('Category'))
                    ->relationship('category', 'name'),
            ])
            ->recordActions([
                Action::make('view')
                    ->label(__('View on the site'))
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (BlogPost $record) => route('blog.show', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (BlogPost $record) => $record->isPublished()),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBlogPosts::route('/'),
            'create' => CreateBlogPost::route('/create'),
            'edit' => EditBlogPost::route('/{record}/edit'),
        ];
    }
}
