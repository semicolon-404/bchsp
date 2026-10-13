<?php

namespace App\Console\Commands;

use App\Models\Announcement;
use App\Models\AnnouncementType;
use DOMDocument;
use DOMXPath;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Brings the announcements of the hospital's old website (Joomla, at /bc/) into this one: the news and
 * job notices, and the procurement notices. Of each: its title, the day it came out, its words, and
 * the links to its documents, which the old site kept on Google Drive. No pictures are copied.
 *
 *   php artisan old-site:import              everything, oldest pages last
 *   php artisan old-site:import --pages=2    only the first two pages of each list (to try it)
 *   php artisan old-site:import --dry-run    say what would be brought in, bring in nothing
 *   php artisan old-site:import --undo       take out what an earlier run brought in
 *
 * Safe to run again: what was brought in before is skipped (storage/app/old-site.json remembers it).
 */
class ImportOldSite extends Command
{
    protected $signature = 'old-site:import {--pages=0 : How many pages of each list to read; 0 reads them all}
        {--dry-run : Say what would be brought in, and bring in nothing}
        {--undo : Take out what an earlier run brought in}
        {--ip= : The public address of the old site, when its name does not resolve to it from here}';

    protected $description = 'Bring the announcements of the old website (text and document links, no pictures) into this one';

    private const HOST = 'buachedhsp.moph.go.th';

    /** A list on the old site => the type its announcements get here; null = told from the title (news or jobs). */
    private const LISTS = [
        '/bc/index.php/khawsar-prachasamphanth/khawsar-pra-samphanth' => null,
        '/bc/index.php/khawsar-prachasamphanth/cad-sux-cad-cang-khaw-prakwd-rakha' => 'procurement',
    ];

    /** Words in a title that make it a job notice. */
    private const JOBS = '/รับสมัคร|สอบคัดเลือก|คัดเลือกบุคคล|ผู้มีสิทธิ์สอบ|ผู้มีสิทธิสอบ|ผลสอบ|ผลการสอบ|ลูกจ้างชั่วคราว|ลูกจ้างรายวัน/u';

    private const PER_PAGE = 10;

    private ?string $ip = null;

    public function handle(): int
    {
        if ($this->option('undo')) {
            return $this->undo();
        }

        $this->ip = $this->option('ip') ?: null;
        $types = AnnouncementType::pluck('id', 'slug');
        $done = $this->remembered();
        $counts = ['brought in' => 0, 'already here' => 0, 'could not be read' => 0];

        foreach (self::LISTS as $list => $type) {
            $this->info("Reading $list");

            foreach ($this->addresses($list) as $address) {
                if (isset($done[$address])) {
                    $counts['already here']++;

                    continue;
                }

                try {
                    $found = $this->read($address);
                } catch (Throwable $e) {
                    $this->warn("  could not read $address: ".$e->getMessage());
                    $counts['could not be read']++;

                    continue;
                }

                $slug = $type ?? (preg_match(self::JOBS, $found['title']) ? 'jobs' : 'news');

                // The same title on the same day is the same announcement, however it got here.
                if (Announcement::where('title', $found['title'])->whereDate('starts_at', $found['at']->toDateString())->exists()) {
                    $counts['already here']++;

                    continue;
                }

                $this->line('  '.$found['at']->toDateString()."  [$slug]  ".Str::limit($found['title'], 80).'  ('.count($found['links']).' links)');
                $counts['brought in']++;

                if ($this->option('dry-run')) {
                    continue;
                }

                $announcement = new Announcement([
                    'announcement_type_id' => $types[$slug] ?? null,
                    'title' => $found['title'],
                    'body' => $found['body'],
                    'links' => $found['links'] ?: null,
                    'starts_at' => $found['at'],
                    'status' => Announcement::STATUS_PUBLISHED,
                ]);
                $announcement->created_at = $found['at'];
                $announcement->save();

                $done[$address] = $announcement->id;
                $this->remember($done);
            }
        }

        foreach ($counts as $what => $count) {
            $this->line(str_pad((string) $count, 5, ' ', STR_PAD_LEFT)."  $what");
        }

        if ($this->option('dry-run')) {
            $this->comment('Nothing was brought in: this was a dry run.');
        }

        return self::SUCCESS;
    }

    /**
     * The addresses of every announcement in a list, newest first, page after page.
     *
     * @return list<string>
     */
    private function addresses(string $list): array
    {
        $found = [];
        $limit = (int) $this->option('pages');

        for ($page = 0; $limit === 0 || $page < $limit; $page++) {
            try {
                $html = $this->fetch($list.'?start='.($page * self::PER_PAGE));
            } catch (Throwable $e) {
                // What was listed so far is still brought in; running the command again picks up the rest.
                $this->warn('  the list stopped answering at page '.($page + 1).': '.Str::limit($e->getMessage(), 120));

                break;
            }

            preg_match_all('~href="('.preg_quote($list, '~').'/[^"?#]+)"~', $html, $matches);
            $new = array_values(array_diff(array_unique($matches[1]), $found));

            if ($new === []) {
                break;
            }

            $found = [...$found, ...$new];
        }

        return $found;
    }

    /**
     * One announcement of the old site: its title, its day, its words and the documents it links to.
     *
     * @return array{title: string, at: Carbon, body: ?string, links: list<array{label: string, url: string}>}
     */
    private function read(string $address): array
    {
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$this->fetch($address), LIBXML_NOERROR | LIBXML_NOWARNING);
        $find = new DOMXPath($document);

        $title = $this->tidy((string) $find->query('//h1[@itemprop="headline"]')->item(0)?->textContent);
        $day = $find->query('//meta[@property="datePublished"]/@content')->item(0)?->nodeValue;
        $text = $find->query('//div[@property="text"]')->item(0);

        if ($title === '' || ! $day) {
            throw new \RuntimeException('no title or no day on the page');
        }

        $links = [];
        $words = '';

        if ($text) {
            // Its pictures are left behind, with whatever was said of them to people who cannot see them.
            foreach (iterator_to_array($find->query('.//img | .//script | .//style', $text)) as $unwanted) {
                $unwanted->parentNode->removeChild($unwanted);
            }

            foreach ($find->query('.//a[@href]', $text) as $anchor) {
                $url = trim($anchor->getAttribute('href'));

                if (preg_match('~^https://[^\s"\'<>]+$~', $url) && ! in_array($url, array_column($links, 'url'), true) && count($links) < Announcement::MAX_FILES) {
                    $label = Str::limit($this->tidy($anchor->textContent), 150, '…');
                    $links[] = ['label' => $label !== '' ? $label : $title, 'url' => $url];
                }
            }

            $words = trim((string) preg_replace('/[ \t\x{00a0}]+/u', ' ', (string) preg_replace('/\s*\n\s*/u', "\n", $text->textContent)));
        }

        // Words that only repeat the title, or the names of the links, say nothing more: no body then.
        $said = $this->tidy(str_replace([$title, ...array_column($links, 'label')], '', $words));
        $body = mb_strlen($said) < 15 ? null : implode('', array_map(fn (string $line) => '<p>'.e($line).'</p>', array_filter(array_map('trim', explode("\n", $words)))));

        return [
            'title' => Str::limit($title, 245, '…'),
            'at' => Carbon::parse($day)->setTimezone(config('app.timezone'))->min(now()->subMinute()),
            'body' => $body,
            'links' => $links,
        ];
    }

    private function tidy(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /** The page at an address of the old site. From inside the hospital its name leads to an inner address that does not answer: then its public one is used. */
    private function fetch(string $path): string
    {
        usleep(150_000);    // one at a time and unhurried: the old site is a small server

        $get = fn (?string $ip) => Http::timeout(30)->retry(4, 2000)
            ->withOptions($ip ? ['curl' => [CURLOPT_RESOLVE => [self::HOST.':443:'.$ip]]] : [])
            ->get('https://'.self::HOST.$path)->throw()->body();

        try {
            return $get($this->ip);
        } catch (Throwable $e) {
            if ($this->ip !== null) {
                throw $e;
            }

            // Ask a public name server where the site is, and go there.
            $this->ip = (string) collect(Http::timeout(15)->get('https://dns.google/resolve', ['name' => self::HOST, 'type' => 'A'])->json('Answer') ?? [])
                ->firstWhere('type', 1)['data'] ?? '';

            if ($this->ip === '') {
                throw $e;
            }

            return $get($this->ip);
        }
    }

    private function undo(): int
    {
        $done = $this->remembered();
        $removed = 0;

        foreach (Announcement::whereKey(array_values($done))->get() as $announcement) {
            $announcement->forceDelete();
            $removed++;
        }

        File::delete($this->file());
        $this->info("$removed announcements brought in from the old site were taken out.");

        return self::SUCCESS;
    }

    /** @return array<string, int> address on the old site => the announcement it became */
    private function remembered(): array
    {
        return json_decode((string) @file_get_contents($this->file()), true) ?: [];
    }

    private function remember(array $done): void
    {
        File::put($this->file(), json_encode($done, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function file(): string
    {
        // Elsewhere in the tests, so that a run of them never touches what a real import remembered.
        return config('bchsp.old_site_file') ?: storage_path('app/old-site.json');
    }
}
