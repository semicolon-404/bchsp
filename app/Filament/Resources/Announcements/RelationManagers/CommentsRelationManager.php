<?php

namespace App\Filament\Resources\Announcements\RelationManagers;

use App\Models\AnnouncementComment;
use App\Support\OptimizedUpload;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Under a post in the panel: what the staff write to each other about it, words and pictures, oldest first.
 * Only the panel shows them. Whoever can open the post can comment; each takes back only their own, and an
 * administrator any. The department, the administrators and those who commented before hear of a new one.
 */
class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Comments');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('body')
                ->label(__('Comment'))
                ->rows(3)
                ->maxLength(2000)
                ->requiredWithout('images')
                ->columnSpanFull(),
            OptimizedUpload::on(FileUpload::make('images'))
                ->label(__('Pictures'))
                ->image()
                ->multiple()
                ->maxFiles(6)
                ->maxSize(8192)
                ->disk('public')
                ->directory('announcements/comments')
                ->visibility('public')
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->defaultSort('created_at')
            ->paginated(false)
            ->emptyStateHeading(__('No comments yet'))
            ->emptyStateDescription(__('Ask, answer or add a picture: the department and the administrators are told.'))
            ->emptyStateIcon(Heroicon::OutlinedChatBubbleLeftRight)
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('user.name')
                            ->weight('semibold')
                            ->placeholder(__('Someone'))
                            ->description(fn (AnnouncementComment $record) => $record->created_at?->diffForHumans()),
                        TextColumn::make('body')
                            ->wrap()
                            ->placeholder(''),
                        ImageColumn::make('images')
                            ->disk('public')
                            ->imageHeight(88)
                            ->limit(6),
                    ])->space(2),
                ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('Write a comment'))
                    ->icon(Heroicon::OutlinedChatBubbleLeft)
                    ->modalHeading(__('Write a comment'))
                    ->createAnother(false)
                    ->mutateDataUsing(fn (array $data) => [...$data, 'user_id' => auth()->id()]),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->visible(fn (AnnouncementComment $record) => $record->user_id === auth()->id() || auth()->user()?->isAdmin()),
            ]);
    }
}
