<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\Announcements\Pages\EditAnnouncement;
use App\Models\Announcement;
use App\Models\User;
use App\Support\Reports;
use App\Support\Searches;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/** A QR code to a page of the site, and what readers search for and do not find. */
class QrAndSearchWordsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_qr_code_is_drawn_for_a_page_of_this_site_and_for_nowhere_else(): void
    {
        $response = $this->get('/qr.svg?to=/announcements/flu-vaccine')->assertOk();

        $this->assertStringContainsString('image/svg+xml', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('<svg', $response->getContent());
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('attachment', $this->get('/qr.svg?to=/&download=1')->headers->get('Content-Disposition'));

        foreach (['', 'https://evil.example', '//evil.example', '/\\evil.example', 'announcements', '/a b'] as $elsewhere) {
            $this->get('/qr.svg?'.http_build_query(['to' => $elsewhere]))->assertNotFound();
        }

        $this->get('/qr.svg?to[]=1')->assertNotFound();
    }

    public function test_an_announcement_offers_its_qr_code_to_readers_and_to_staff(): void
    {
        $post = Announcement::factory()->create();
        $code = route('qr', ['to' => '/announcements/'.$post->slug]);

        $this->get(route('announcements.show', $post))->assertSee('href="'.e($code).'"', false)->assertSee('QR code');

        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
        Livewire::test(EditAnnouncement::class, ['record' => $post->getRouteKey()])
            ->assertActionExists('qr')
            ->mountAction('qr')
            ->assertActionMounted('qr');

        $this->assertStringContainsString('src="'.e($code).'"', view('filament.qr', ['url' => $code])->render());
    }

    public function test_what_is_searched_is_counted_by_its_words_once_a_visit_and_robots_are_not_counted(): void
    {
        Announcement::factory()->create(['title' => 'Flu vaccine days']);

        $this->get('/search?q=Flu')->assertOk();
        $this->get('/search?q=flu')->assertOk();
        $this->get('/search?q=%20%20FLU%20')->assertOk();
        $this->get('/search?q=dialysis')->assertOk();
        // Too short to be searched at all, and a robot.
        $this->get('/search?q=a');
        $this->withHeader('User-Agent', 'Googlebot/2.1')->get('/search?q=robots');

        $this->assertSame([['term' => 'dialysis', 'searches' => 1, 'found' => 0], ['term' => 'flu', 'searches' => 1, 'found' => 1]],
            DB::table('search_terms')->orderBy('term')->get(['term', 'searches', 'found'])->map(fn ($row) => (array) $row)->all());

        // Another reader counts.
        $this->flushSession();
        $this->withHeader('User-Agent', 'Mozilla/5.0')->get('/search?q=flu');
        $this->assertSame(2, (int) DB::table('search_terms')->where('term', 'flu')->value('searches'));
    }

    public function test_the_report_says_what_was_searched_most_and_what_found_nothing(): void
    {
        $day = today()->toDateString();
        DB::table('search_terms')->insert([
            ['day' => $day, 'term' => 'flu', 'searches' => 5, 'found' => 3],
            ['day' => today()->subDay()->toDateString(), 'term' => 'flu', 'searches' => 2, 'found' => 3],
            ['day' => $day, 'term' => 'dialysis', 'searches' => 4, 'found' => 0],
            ['day' => today()->subYears(2)->toDateString(), 'term' => 'long ago', 'searches' => 9, 'found' => 0],
        ]);

        $summary = Reports::summary(now()->subMonth(), now());

        $this->assertSame([['term' => 'flu', 'searches' => 7], ['term' => 'dialysis', 'searches' => 4]], $summary['searches']['top']);
        $this->assertSame([['term' => 'dialysis', 'searches' => 4]], $summary['searches']['empty']);
        $this->assertContains(['dialysis', 4], Reports::rows($summary, 'A month'));

        $page = view('reports.body', ['summary' => $summary])->render();
        $this->assertStringContainsString('Searched for and not found', $page);
        $this->assertStringContainsString('dialysis', $page);

        // Old days are dropped.
        $this->assertSame(1, Searches::prune());
        $this->assertSame(3, DB::table('search_terms')->count());
    }
}
