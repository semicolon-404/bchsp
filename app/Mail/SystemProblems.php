<?php

namespace App\Mail;

use App\Support\SiteSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Sent to the administrators when something on System status is wrong (App\Console\Commands\NotifyOfProblems):
 * last night's backup did not run, the disk is nearly full. Plain text, so that it reads in any mail program.
 */
class SystemProblems extends Mailable
{
    use Queueable;

    /** @param  list<array{name: string, state: string, found: string, advice: ?string}>  $problems */
    public function __construct(public array $problems) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __(':name: something on the website needs looking at', ['name' => SiteSettings::name()]));
    }

    public function content(): Content
    {
        return new Content(text: 'mail.system-problems');
    }
}
