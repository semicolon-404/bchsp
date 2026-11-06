<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The bell in the site's bar (components/site/account): opening one of one's notifications marks it read and goes on
 * to what it is about; "all read" clears the count. Only one's own, and only on to an address of this site.
 */
class MyNotificationsController extends Controller
{
    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $to = (string) ($notification->data['actions'][0]['url'] ?? '');
        $sameSite = $to !== '' && (str_starts_with($to, '/') && ! str_starts_with($to, '//') || parse_url($to, PHP_URL_HOST) === $request->getHost());

        return redirect()->to($sameSite ? $to : route('filament.admin.pages.notifications'));
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }
}
