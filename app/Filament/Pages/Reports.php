<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\AdminOnly;
use App\Filament\Concerns\TranslatesTitle;
use App\Models\Announcement;
use App\Support\Reports as Report;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * The report for the hospital's executives: for a month, a quarter or a fiscal
 * year, how much the site was used, what was published, how complete the ITA
 * is, and what each member of staff did (App\Support\Reports). It can be
 * printed (or saved as a PDF from the print window) and downloaded for Excel.
 */
class Reports extends Page
{
    use AdminOnly;
    use TranslatesTitle;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?string $title = 'Reports';

    protected static ?int $navigationSort = 7;

    protected string $view = 'filament.pages.reports';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(['period' => 'year', 'year' => Announcement::fiscalYearOf(now()), 'month' => now()->month, 'quarter' => 1]);
    }

    public function form(Schema $schema): Schema
    {
        $thisYear = Announcement::fiscalYearOf(now());

        return $schema
            ->statePath('data')
            ->components([
                Section::make(__('Period'))
                    ->columns(3)
                    ->components([
                        Select::make('period')
                            ->label(__('Report for'))
                            ->options(array_map('__', Report::PERIODS))
                            ->selectablePlaceholder(false)
                            ->live(),
                        Select::make('year')
                            ->label(__('Fiscal year'))
                            // This year and the four before it.
                            ->options(array_combine(range($thisYear, $thisYear - 4), range($thisYear, $thisYear - 4)))
                            ->selectablePlaceholder(false)
                            ->live(),
                        Select::make('month')
                            ->label(__('Month'))
                            // In the order of a fiscal year: October first.
                            ->options(collect([10, 11, 12, 1, 2, 3, 4, 5, 6, 7, 8, 9])->mapWithKeys(fn (int $month) => [$month => now()->startOfYear()->month($month)->locale(app()->getLocale())->translatedFormat('F')])->all())
                            ->selectablePlaceholder(false)
                            ->live()
                            ->visible(fn (Get $get) => $get('period') === 'month'),
                        Select::make('quarter')
                            ->label(__('Quarter'))
                            ->options([1 => __('Quarter 1 (Oct–Dec)'), 2 => __('Quarter 2 (Jan–Mar)'), 3 => __('Quarter 3 (Apr–Jun)'), 4 => __('Quarter 4 (Jul–Sep)')])
                            ->selectablePlaceholder(false)
                            ->live()
                            ->visible(fn (Get $get) => $get('period') === 'quarter'),
                    ]),
            ]);
    }

    /** The period chosen, as the print and download addresses take it. */
    public function choice(): array
    {
        return array_intersect_key($this->data ?? [], array_flip(['period', 'year', 'month', 'quarter']));
    }

    public function getPeriodName(): string
    {
        return Report::period($this->choice())[2];
    }

    /** @return array<string, mixed> */
    public function getSummary(): array
    {
        [$from, $until] = Report::period($this->choice());

        return Report::summary($from, $until);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label(__('Print or save as PDF'))
                ->icon(Heroicon::OutlinedPrinter)
                ->url(fn () => route('reports.print', $this->choice()))
                ->openUrlInNewTab(),
            Action::make('download')
                ->label(__('Download for Excel'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->url(fn () => route('reports.download', $this->choice())),
        ];
    }
}
