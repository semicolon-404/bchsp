<?php

namespace App\Mail;

use App\Support\SiteSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Carbon;

/** The website's report of one month, sent to the administrators (App\Console\Commands\SendMonthlyReport). */
class MonthlyReport extends Mailable
{
    use Queueable;

    /** @param  array<string, mixed>  $summary  from App\Support\Reports::summary */
    public function __construct(public array $summary, public Carbon $month) {}

    public function monthName(): string
    {
        return $this->month->locale(app()->getLocale())->translatedFormat('F ').($this->month->year + (app()->getLocale() === 'th' ? 543 : 0));
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __(':name: website report for :month', ['name' => SiteSettings::name(), 'month' => $this->monthName()]));
    }

    public function content(): Content
    {
        return new Content(html: 'mail.monthly-report', with: ['monthName' => $this->monthName()]);
    }
}
