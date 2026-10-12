<?php

namespace App\Filament\Resources\ClinicSessions;

use App\Filament\Concerns\NotForDepartments;
use App\Filament\Concerns\TranslatesNames;
use App\Filament\Resources\ClinicSessions\Pages\ManageClinicSessions;
use App\Models\ClinicSession;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
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
 * The weekly schedule of clinics and services: what is open on which days,
 * from when to when. One line can cover several days ("Monday to Friday,
 * 08.00–16.00"); a clinic with a morning and an afternoon is two lines.
 */
class ClinicSessionResource extends Resource
{
    use NotForDepartments, TranslatesNames;

    protected static ?string $model = ClinicSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Hospital';

    protected static ?string $navigationLabel = 'Clinic schedule';

    protected static ?string $modelLabel = 'clinic session';

    protected static ?string $pluralModelLabel = 'clinic schedule';

    protected static ?int $navigationSort = 0;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        $time = fn (string $name) => TextInput::make($name)
            ->required()
            ->regex(ClinicSession::TIME_PATTERN)
            ->validationMessages(['regex' => __('Write the time as hours and minutes on the 24-hour clock, e.g. 08:00 or 16:30.')])
            ->placeholder('08:00')
            ->maxLength(5);

        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Clinic or service'))
                    ->required()
                    ->maxLength(150)
                    ->placeholder(__('Diabetes clinic')),
                TextInput::make('name_en')
                    ->label(__('Name in English (optional)'))
                    ->maxLength(150),
                CheckboxList::make('days')
                    ->label(__('Days it is held'))
                    ->options(array_map('__', ClinicSession::DAYS))
                    ->required()
                    ->columns(4)
                    ->bulkToggleable()
                    ->columnSpanFull(),
                $time('starts_at')->label(__('From')),
                $time('ends_at')->label(__('Until'))->helperText(__('For a service open all day and night, write 00:00 to 23:59.')),
                TextInput::make('note')
                    ->label(__('Note (optional)'))
                    ->maxLength(200)
                    ->placeholder(__('By appointment'))
                    ->columnSpanFull(),
                Select::make('department_id')
                    ->label(__('Department (optional)'))
                    ->relationship('department', 'name')
                    ->preload()
                    ->searchable(),
                Toggle::make('is_active')
                    ->label(__('Shown on the site'))
                    ->default(true)
                    ->inline(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->label(__('Clinic or service'))
                    ->searchable()
                    ->description(fn (ClinicSession $record) => $record->note),
                TextColumn::make('days')
                    ->label(__('Days it is held'))
                    ->state(fn (ClinicSession $record) => count($record->days ?? []) === 7
                        ? __('Every day')
                        : collect(ClinicSession::DAYS)->filter(fn ($name, $day) => $record->isHeldOn($day))->map(fn ($name) => __($name))->implode(' '))
                    ->wrap(),
                TextColumn::make('hours')
                    ->label(__('Hours'))
                    ->state(fn (ClinicSession $record) => $record->hours()),
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
            'index' => ManageClinicSessions::route('/'),
        ];
    }
}
