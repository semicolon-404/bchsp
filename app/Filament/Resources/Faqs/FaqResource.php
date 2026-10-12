<?php

namespace App\Filament\Resources\Faqs;

use App\Filament\Concerns\NotForDepartments;
use App\Filament\Concerns\TranslatesNames;
use App\Filament\Resources\Faqs\Pages\ManageFaqs;
use App\Models\Faq;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
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
 * The questions people ask the hospital, each with its answer, as the public reads them at /faq.
 * Drag the rows to put them in order.
 */
class FaqResource extends Resource
{
    use NotForDepartments, TranslatesNames;

    protected static ?string $model = Faq::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Hospital';

    protected static ?string $navigationLabel = 'Questions people ask';

    protected static ?string $modelLabel = 'question';

    protected static ?string $pluralModelLabel = 'questions people ask';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'question';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('question')
                    ->label(__('Question'))
                    ->required()
                    ->maxLength(300)
                    ->placeholder(__('What should I bring on my first visit?'))
                    ->columnSpanFull(),
                Textarea::make('answer')
                    ->label(__('Answer'))
                    ->required()
                    ->rows(5)
                    ->maxLength(3000)
                    ->helperText(__('A few plain lines. Where a longer page has the details, say where it is.'))
                    ->columnSpanFull(),
                TextInput::make('question_en')
                    ->label(__('Question in English (optional)'))
                    ->maxLength(300)
                    ->columnSpanFull(),
                Textarea::make('answer_en')
                    ->label(__('Answer in English (optional)'))
                    ->rows(3)
                    ->maxLength(3000)
                    ->columnSpanFull(),
                TextInput::make('group')
                    ->label(__('Heading to list it under (optional)'))
                    ->maxLength(100)
                    // The headings already in use are offered, so that one is not spelt two ways.
                    ->datalist(fn () => Faq::query()->whereNotNull('group')->distinct()->orderBy('group')->pluck('group')->all()),
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
                TextColumn::make('question')
                    ->label(__('Question'))
                    ->searchable()
                    ->wrap(),
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
            'index' => ManageFaqs::route('/'),
        ];
    }
}
