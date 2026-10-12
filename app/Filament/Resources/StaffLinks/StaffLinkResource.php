<?php

namespace App\Filament\Resources\StaffLinks;

use App\Filament\Concerns\AdminOnly;
use App\Filament\Concerns\TranslatesNames;
use App\Filament\Resources\StaffLinks\Pages\ManageStaffLinks;
use App\Models\StaffLink;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Links to services that live elsewhere: for staff (the "For staff" page) and for
 * the public ("Services online"). Drag the rows to put them in order.
 */
class StaffLinkResource extends Resource
{
    use AdminOnly, TranslatesNames;

    protected static ?string $model = StaffLink::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'Hospital';

    protected static ?string $navigationLabel = 'Links to services';

    protected static ?string $modelLabel = 'service';

    protected static ?string $pluralModelLabel = 'services for staff';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(150)
                    ->columnSpanFull(),
                TextInput::make('url')
                    ->label(__('Web address'))
                    ->required()
                    ->url()
                    ->startsWith(['https://', 'http://'])
                    ->maxLength(500)
                    ->columnSpanFull(),
                Select::make('audience')
                    ->label(__('Who it is for'))
                    ->options(collect(StaffLink::AUDIENCES)->map(fn (string $label) => __($label))->all())
                    ->default('staff')
                    ->required()
                    ->selectablePlaceholder(false)
                    ->columnSpanFull(),
                TextInput::make('group')
                    ->label(__('Heading to list it under (optional)'))
                    ->maxLength(100)
                    // The headings already in use are offered, so that one is not spelt two ways.
                    ->datalist(fn () => StaffLink::query()->whereNotNull('group')->distinct()->orderBy('group')->pluck('group')->all()),
                TextInput::make('note')
                    ->label(__('Note (optional)'))
                    ->maxLength(200)
                    ->placeholder(__('Opens only on the hospital\'s network')),
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
                    ->label(__('Name'))
                    ->searchable()
                    ->description(fn (StaffLink $record) => $record->url),
                TextColumn::make('audience')
                    ->label(__('Who it is for'))
                    ->formatStateUsing(fn (string $state) => __($state === 'public' ? 'The public' : 'Staff'))
                    ->badge()
                    ->color(fn (string $state) => $state === 'public' ? 'success' : 'gray'),
                TextColumn::make('group')
                    ->label(__('Heading'))
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),
                ToggleColumn::make('is_active')
                    ->label(__('Shown on the site')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStaffLinks::route('/'),
        ];
    }
}
