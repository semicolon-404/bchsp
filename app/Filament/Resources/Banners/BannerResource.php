<?php

namespace App\Filament\Resources\Banners;

use App\Filament\Concerns\TranslatesNames;
use App\Filament\Resources\Banners\Pages\ManageBanners;
use App\Models\Banner;
use App\Support\OptimizedUpload;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Pictures on the home page: wide banners across the top, and posters in the
 * grid of posters. Each runs between two dates, so one can be prepared ahead
 * and takes itself down afterwards; one can also open over the page when the
 * site is first opened.
 */
class BannerResource extends Resource
{
    use TranslatesNames;

    protected static ?string $model = Banner::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Announcements';

    protected static ?string $navigationLabel = 'Slides and posters';

    protected static ?string $modelLabel = 'slide or poster';

    protected static ?string $pluralModelLabel = 'slides and posters';

    // First in its group: it is what staff change most often.
    protected static ?int $navigationSort = 0;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                OptimizedUpload::on(FileUpload::make('image_path'))
                    ->label(__('Picture'))
                    ->image()
                    // No SVG: it can carry a script.
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->disk('public')
                    ->directory('banners')
                    ->visibility('public')
                    ->maxSize(8192)
                    ->required()
                    ->helperText(__('Any shape: the picture is shown whole. A wide picture (about 1600 × 600) fills a slide best. Up to 8 MB; it is made smaller by itself.'))
                    ->columnSpanFull(),
                TextInput::make('title')
                    ->label(__('What it says'))
                    ->required()
                    ->maxLength(150)
                    ->helperText(__('Read aloud to people who cannot see the picture, e.g. "Free flu vaccine, 1–15 June, at the outpatient department".'))
                    ->columnSpanFull(),
                TextInput::make('url')
                    ->label(__('Link (optional)'))
                    ->url()
                    ->startsWith(['https://', 'http://'])
                    ->maxLength(500)
                    ->helperText(__('Where a press leads: an announcement, an article. A page on another website opens in a new tab.'))
                    ->columnSpanFull(),
                Select::make('placement')
                    ->label(__('Where it shows'))
                    ->options(array_map('__', Banner::PLACES))
                    ->default('banner')
                    ->required()
                    ->selectablePlaceholder(false),
                Toggle::make('is_popup')
                    ->label(__('Also open over the page when the site is opened'))
                    ->inline(false)
                    ->helperText(__('One at a time: the first in order. A visitor who closes it does not see it again.')),
                DateTimePicker::make('starts_at')
                    ->label(__('Show from'))
                    ->default(fn () => now())
                    ->required()
                    ->seconds(false),
                DateTimePicker::make('ends_at')
                    ->label(__('Show until (optional)'))
                    ->seconds(false)
                    ->after('starts_at')
                    ->helperText(__('Left blank, it stays until switched off.')),
                Toggle::make('is_active')
                    ->label(__('Switched on'))
                    ->default(true)
                    ->inline(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                ImageColumn::make('image_path')
                    ->label('')
                    ->disk('public')
                    ->imageHeight(54),
                TextColumn::make('title')
                    ->label(__('What it says'))
                    ->searchable()
                    ->wrap()
                    ->description(fn (Banner $record) => $record->url),
                TextColumn::make('placement')
                    ->label(__('Where'))
                    ->formatStateUsing(fn (string $state) => __($state === 'poster' ? 'Poster' : 'Slide'))
                    ->badge()
                    ->color('gray'),
                IconColumn::make('is_popup')
                    ->label(__('Opens over the page'))
                    ->boolean(),
                TextColumn::make('state')
                    ->label(__('Status'))
                    ->state(fn (Banner $record) => $record->state())
                    ->formatStateUsing(fn (string $state) => __($state))
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Showing' => 'success',
                        'Scheduled' => 'warning',
                        default => 'gray',
                    }),
                ToggleColumn::make('is_active')
                    ->label(__('Switched on')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBanners::route('/'),
        ];
    }
}
