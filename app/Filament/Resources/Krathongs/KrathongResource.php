<?php

namespace App\Filament\Resources\Krathongs;

use App\Filament\Resources\Krathongs\Pages\ManageKrathongs;
use App\Models\Festival;
use App\Models\Krathong;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * The krathongs floated on the home page's river at Loy Krathong (App\Models\Krathong): a department floats its own,
 * one to a festival, with a short wish; an administrator sees them all and can take one off.
 */
class KrathongResource extends Resource
{
    protected static ?string $model = Krathong::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static string|UnitEnum|null $navigationGroup = 'Festivals';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('Float a krathong');
    }

    public static function getModelLabel(): string
    {
        return __('krathong');
    }

    public static function getPluralModelLabel(): string
    {
        return __('krathongs');
    }

    /** A department sees its own; an administrator, all. */
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getEloquentQuery()->with(['festival', 'department'])
            ->when($user?->postsForOneDepartment(), fn (Builder $query) => $query->where('department_id', $user->department_id));
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        return $user !== null && ($user->isAdmin() || $record->department_id === $user->department_id);
    }

    public static function canDelete(Model $record): bool
    {
        return static::canEdit($record);
    }

    /** The Loy Krathong festivals still to come or going on, to float in. */
    public static function festivals(): Builder
    {
        return Festival::where('theme', 'loykrathong')->where('is_active', true)->whereDate('ends_on', '>=', today())->orderBy('starts_on');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('festival_id')
                    ->label(__('Festival'))
                    ->options(fn () => static::festivals()->pluck('name', 'id'))
                    ->default(fn () => static::festivals()->value('id'))
                    ->required()
                    ->native(false)
                    ->helperText(__('A Loy Krathong festival set by the administrators.')),
                Radio::make('style')
                    ->label(__('Krathong'))
                    ->options(collect(Krathong::STYLES)->map(fn ($label) => __($label))->all())
                    ->default('banana')
                    ->inline()
                    ->required(),
                TextInput::make('wish')
                    ->label(__('Wish'))
                    ->required()
                    ->maxLength(140)
                    ->placeholder(__('Good health to everyone in Buached')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('No krathong yet'))
            ->emptyStateDescription(__('Float your department\'s krathong: it drifts on the home page\'s river through the festival.'))
            ->columns([
                TextColumn::make('department.name')->label(__('Department'))->placeholder(__('The hospital'))->weight('semibold'),
                TextColumn::make('style')->label(__('Krathong'))->formatStateUsing(fn (string $state) => __(Krathong::STYLES[$state] ?? $state)),
                TextColumn::make('wish')->label(__('Wish'))->wrap(),
                TextColumn::make('festival.name')->label(__('Festival'))->color('gray'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageKrathongs::route('/')];
    }
}
