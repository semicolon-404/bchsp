<?php

namespace App\Livewire;

use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Livewire\Component;

/**
 * The bell's news shown as it comes: every few seconds the panel asks whether something new has arrived for this
 * account (a post waiting, approved, sent back) and shows it at the bottom left, where it stays a moment and can be
 * opened. A host with no way to push (no websockets) is asked instead; what was missed is still in the bell.
 */
class NoticePopups extends Component
{
    /** What arrived up to here has been shown, or was there before the page was opened. */
    public string $since = '';

    public function mount(): void
    {
        $this->since = now()->toIso8601String();
    }

    public function check(): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $new = $user->unreadNotifications()->where('created_at', '>', Carbon::parse($this->since))->oldest()->limit(3)->get();

        foreach ($new as $notice) {
            Notification::fromDatabase($notice)->duration(10000)->send();
            $this->since = $notice->created_at->toIso8601String();
        }
    }

    public function render(): string
    {
        return '<div wire:poll.15s="check" hidden></div>';
    }
}
