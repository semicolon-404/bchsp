<?php

namespace App\Filament\Resources\Users;

use App\Enums\Role;
use App\Filament\Concerns\AdminOnly;
use App\Filament\Concerns\TranslatesNames;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Password;
use UnitEnum;

/**
 * The people who can sign in to the panel. Only an administrator sees this:
 * it is where accounts are made, since nobody can sign up.
 *
 * An administrator cannot delete their own account or change their own role,
 * so the panel always has at least one.
 */
class UserResource extends Resource
{
    use AdminOnly, TranslatesNames;

    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Staff';

    protected static ?string $modelLabel = 'staff member';

    protected static ?string $pluralModelLabel = 'staff';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('username')
                    ->label(__('Username'))
                    ->alphaDash()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true)
                    ->requiredWithout('email')
                    ->helperText(__('They sign in with this or with the email. One of the two is needed.')),
                TextInput::make('email')
                    ->label(__('Email'))
                    ->email()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->requiredWithout('username'),
                Select::make('role')
                    ->label(__('Role'))
                    ->options(collect(Role::cases())->mapWithKeys(fn (Role $role) => [$role->value => __($role->label())])->all())
                    ->default(Role::Author->value)
                    ->required()
                    ->selectablePlaceholder(false)
                    ->disabled(fn (?User $record) => $record?->is(auth()->user()) ?? false)
                    ->helperText(__('An author writes and publishes content. An administrator can also change settings, menus and staff.')),
                Select::make('department_id')
                    ->label(__('Posts for one department (optional)'))
                    ->relationship('department', 'name', fn ($query) => $query->orderBy('sort_order')->orderBy('name'))
                    ->preload()
                    ->searchable()
                    ->helperText(__('An author with a department posts on that department\'s board and keeps its page, and reaches nothing else. Left blank, the author posts for the whole hospital.')),
                TextInput::make('password')
                    ->label(__('Password'))
                    ->password()
                    ->revealable()
                    ->rule(Password::min(8))
                    ->required(fn (string $operation) => $operation === 'create')
                    // Left blank on an edit, the password stays as it is.
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->helperText(fn (string $operation) => $operation === 'create'
                        ? __('At least 8 characters. Tell them to change it after the first sign-in.')
                        : __('Leave blank to keep the password they have.')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('username')
                    ->label(__('Username'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('Email'))
                    ->searchable(),
                TextColumn::make('role')
                    ->label(__('Role'))
                    ->formatStateUsing(fn (Role $state) => __($state->label()))
                    ->badge()
                    ->color(fn (Role $state) => $state === Role::Admin ? 'primary' : 'gray'),
                TextColumn::make('department.name')
                    ->label(__('Department'))
                    ->placeholder('—')
                    ->url(fn (User $record) => $record->department ? route('departments.show', $record->department) : null, shouldOpenInNewTab: true)
                    ->color('primary'),
                TextColumn::make('created_at')
                    ->label(__('Added'))
                    ->date()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (User $record) => $record->is(auth()->user())),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
