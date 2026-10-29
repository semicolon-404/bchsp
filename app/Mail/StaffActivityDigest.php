<?php

namespace App\Mail;

use App\Models\AccessLog;
use App\Support\SiteSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/** Each morning, what every account did in the panel the day before (App\Console\Commands\SendStaffActivity). Plain text. */
class StaffActivityDigest extends Mailable
{
    use Queueable;

    /** @param  Collection<int, AccessLog>  $lines */
    public function __construct(public Collection $lines, public Carbon $day) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __(':name: what was done in the panel on :day', ['name' => SiteSettings::name(), 'day' => $this->day->toDateString()]));
    }

    public function content(): Content
    {
        return new Content(text: 'mail.staff-activity');
    }
}
