<?php

namespace App\Support;

use App\Models\Announcement;
use App\Models\LinkCheck;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Do the links to files kept elsewhere (Google Drive) still open?
 *
 * Most documents of the site are not on its own disk but behind such links, and a file deleted or no longer
 * shared leaves a link that leads to an error page with nobody the wiser. A batch of them is looked at every
 * night (routes/console.php, "links:check"), those not looked at for longest first, so each gets its turn
 * every few days without a long run. What is found shows in the panel: "Needs attention", and a filter on
 * the announcements.
 */
class LinkChecks
{
    /** The table follows the announcements: a row for each link of each one that is out, and none for links taken off. */
    public static function sync(): void
    {
        $kept = [];

        Announcement::published()->whereNotNull('links')->select(['id', 'links'])->chunkById(200, function ($announcements) use (&$kept) {
            foreach ($announcements as $announcement) {
                foreach ($announcement->documentLinks() as $link) {
                    $kept[] = LinkCheck::firstOrCreate(
                        ['announcement_id' => $announcement->id, 'url_hash' => sha1($link['url'])],
                        ['url' => $link['url']],
                    )->id;
                }
            }
        });

        LinkCheck::whereNotIn('id', $kept)->delete();
    }

    /** Looks at a batch; returns how many were looked at. */
    public static function run(int $limit = 60): int
    {
        self::sync();

        $rows = LinkCheck::orderByRaw('checked_at is not null')->orderBy('checked_at')->orderBy('id')->limit($limit)->get();

        foreach ($rows as $row) {
            // A look that could not be made (the network, a busy server) leaves what was known as it was.
            $row->update(['state' => self::look($row->url) ?? $row->state, 'checked_at' => now()]);
        }

        return $rows->count();
    }

    /** ok, dead or private; null when it could not be told. */
    public static function look(string $url): ?string
    {
        try {
            $response = Http::timeout(10)->connectTimeout(6)
                ->withUserAgent('Mozilla/5.0 (compatible; bchsp-link-check)')
                ->withOptions(['allow_redirects' => ['max' => 5, 'track_redirects' => true]])
                ->get($url);
        } catch (Throwable) {
            return null;
        }

        $hops = (string) $response->header('X-Guzzle-Redirect-History');
        $last = $hops !== '' ? trim((string) last(explode(',', $hops))) : $url;

        return match (true) {
            // Sent to a sign-in page: the file is not open to everyone.
            str_contains((string) parse_url($last, PHP_URL_HOST), 'accounts.google.') => LinkCheck::PRIVATE,
            in_array($response->status(), [401, 403], true) => LinkCheck::PRIVATE,
            in_array($response->status(), [404, 410], true) => LinkCheck::DEAD,
            $response->successful() => LinkCheck::OK,
            default => null,
        };
    }

    /** How many announcements showing now have a link that does not open. */
    public static function broken(): int
    {
        return Announcement::current()->whereHas('linkChecks', fn ($query) => $query->whereIn('state', [LinkCheck::DEAD, LinkCheck::PRIVATE]))->count();
    }
}
