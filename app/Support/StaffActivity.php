<?php

namespace App\Support;

use App\Filament\Resources\AccessLogs\AccessLogResource;
use App\Models\AccessLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * What the accounts did in the panel, read off the access log, in words: for the card on the dashboard and the
 * morning email to the administrators (App\Console\Commands\SendStaffActivity).
 */
class StaffActivity
{
    /** @return Collection<int, AccessLog> */
    public static function latest(int $count = 10): Collection
    {
        return AccessLog::with('user')->where('event', '!=', 'logout')->latest('id')->limit($count)->get();
    }

    /** @return Collection<int, AccessLog> */
    public static function on(Carbon $day): Collection
    {
        return AccessLog::with('user')->where('event', '!=', 'logout')
            ->whereBetween('created_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])->oldest('id')->get();
    }

    /** "Malee: Changed announcement #12 (title, body)". */
    public static function describe(AccessLog $line): string
    {
        $who = $line->user?->name ?? ($line->identifier ?: __('Someone'));
        $what = __(AccessLogResource::EVENTS[$line->event] ?? $line->event);
        $item = $line->subject_type ? ' '.$line->subject_type.' #'.$line->subject_id : '';
        $details = str_starts_with($line->event, 'admin.') && filled($line->identifier) ? ' ('.$line->identifier.')' : '';

        return $who.': '.$what.$item.$details;
    }
}
