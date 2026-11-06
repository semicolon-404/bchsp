<?php

namespace App\Filament\Resources\Complaints;

use App\Filament\Concerns\TranslatesNames;
use App\Filament\Resources\Complaints\Pages\ManageComplaints;
use App\Models\Complaint;
use App\Support\SiteSettings;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ViewField;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Complaints, suggestions and praise sent in through the site (/complaint), and what is done about each.
 * For administrators only: who sent a matter is personal, and a report of wrongdoing must not be read by
 * whoever it is about.
 *
 * Nothing is written here by staff as if by the sender: a matter comes only from the form. Staff read it,
 * move it along (received, being checked, being dealt with, finished or not taken up) and write the reply the
 * sender reads on the tracking page; a note beside it is for staff alone.
 */
class ComplaintResource extends Resource
{
    use TranslatesNames;

    /** An administrator, or a department's account for what is about its own groups of work. */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->isAdmin() || $user?->postsForOneDepartment());
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleTo(auth()->user());
    }

    public static function canDelete(Model $record): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    protected static ?string $model = Complaint::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Hospital';

    protected static ?string $navigationLabel = 'Complaints';

    protected static ?string $modelLabel = 'matter';

    protected static ?string $pluralModelLabel = 'complaints and suggestions';

    protected static ?int $navigationSort = 7;

    protected static ?string $recordTitleAttribute = 'code';

    /** Those not yet looked at, beside the menu entry. */
    public static function getNavigationBadge(): ?string
    {
        $new = static::getEloquentQuery()->where('status', 'received')->count();

        return $new ? (string) $new : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function colourOf(string $status): string
    {
        return ['received' => 'danger', 'checking' => 'warning', 'working' => 'info', 'done' => 'success', 'rejected' => 'gray'][$status] ?? 'gray';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                // What was sent, to read and not to change (resources/views/filament/complaint.blade.php).
                ViewField::make('matter')
                    ->hiddenLabel()
                    ->view('filament.complaint')
                    ->dehydrated(false),
                Select::make('status')
                    ->label(__('Where it stands'))
                    ->options(fn () => collect(Complaint::STATUSES)->map(fn (string $name) => __($name))->all())
                    ->required()
                    ->selectablePlaceholder(false)
                    ->native(false),
                Textarea::make('reply')
                    ->label(__('Reply to the sender'))
                    ->helperText(__('The sender reads this on the tracking page. Say what was done, plainly; name nobody who should not be named.'))
                    ->rows(5)
                    ->maxLength(3000),
                Textarea::make('internal_note')
                    ->label(__('Note for staff only'))
                    ->helperText(__('Never shown to the sender.'))
                    ->rows(3)
                    ->maxLength(3000),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('workGroup'))
            ->columns([
                TextColumn::make('code')
                    ->label(__('Tracking number'))
                    ->fontFamily('mono')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('title')
                    ->label(__('Subject'))
                    ->description(fn (Complaint $record) => __(Complaint::KINDS[$record->kind] ?? 'Anything else'))
                    ->searchable()
                    ->wrap()
                    ->extraCellAttributes(['style' => 'min-width: 18rem']),
                TextColumn::make('workGroup.name')
                    ->label(__('Group of work'))
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('Where it stands'))
                    ->formatStateUsing(fn (string $state) => __(Complaint::STATUSES[$state] ?? $state))
                    ->badge()
                    ->color(fn (string $state) => self::colourOf($state)),
                TextColumn::make('created_at')
                    ->label(__('Sent in'))
                    ->since()
                    ->sortable()
                    // Past the day it should have been dealt with by.
                    ->description(fn (Complaint $record) => $record->isOverdue() ? __('Past its :count days', ['count' => SiteSettings::complaintDays()]) : null)
                    ->color(fn (Complaint $record) => $record->isOverdue() ? 'danger' : null),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('Where it stands'))
                    ->options(fn () => collect(Complaint::STATUSES)->map(fn (string $name) => __($name))->all()),
                SelectFilter::make('kind')
                    ->label(__('Kind of matter'))
                    ->options(fn () => collect(Complaint::KINDS)->map(fn (string $name) => __($name))->all()),
                TernaryFilter::make('open')
                    ->label(__('Still open'))
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotIn('status', Complaint::CLOSED),
                        false: fn (Builder $query) => $query->whereIn('status', Complaint::CLOSED),
                    ),
            ])
            ->recordActions([
                EditAction::make()
                    ->label(__('Open'))
                    ->icon(Heroicon::OutlinedEye)
                    ->modalHeading(fn (Complaint $record) => $record->code)
                    ->modalWidth(Width::FourExtraLarge)
                    // Whoever moves it along is the one handling it.
                    ->mutateDataUsing(fn (array $data) => $data + ['handled_by' => auth()->id()]),
                DeleteAction::make()
                    // Removing a matter for good is the administrators' alone.
                    ->visible(fn () => (bool) auth()->user()?->isAdmin())
                    ->modalDescription(__('The matter, who sent it and what was attached are removed for good. The sender can no longer follow it.')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageComplaints::route('/'),
        ];
    }
}
