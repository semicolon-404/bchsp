<?php

namespace Tests\Feature;

use App\Filament\Resources\SitePages\Pages\CreateSitePage;
use App\Filament\Resources\SitePages\Pages\EditSitePage;
use App\Http\Middleware\SetLocale;
use App\Models\SitePage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SitePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_page_is_read_at_its_own_address(): void
    {
        SitePage::factory()->create(['slug' => 'history', 'title' => 'History of the hospital', 'body' => '<h2>Founded</h2><p>Opened as a ten-bed hospital.</p>']);

        $this->get('/p/history')
            ->assertOk()
            ->assertSee('<title>History of the hospital · ', false)
            ->assertSee('Founded')
            ->assertSee('Opened as a ten-bed hospital.');
    }

    public function test_a_page_of_a_group_leads_on_to_the_one_before_and_the_one_after(): void
    {
        // The pages every site begins with (the organisation chart, the executives) are set aside: this is about three of its own.
        SitePage::where('section', 'about')->delete();
        $history = SitePage::factory()->create(['title' => 'History', 'section' => 'about', 'position' => 1]);
        $vision = SitePage::factory()->create(['title' => 'Vision', 'section' => 'about', 'position' => 2]);
        $structure = SitePage::factory()->create(['title' => 'Structure', 'section' => 'about', 'position' => 3]);
        SitePage::factory()->create(['title' => 'Hidden', 'section' => 'about', 'position' => 2, 'is_published' => false]);
        $alone = SitePage::factory()->create(['title' => 'Privacy']);

        $this->get(route('pages.show', $vision))
            ->assertOk()
            ->assertSee('href="'.route('about').'"', false)
            ->assertSee('<a href="'.route('pages.show', $history).'" rel="prev"', false)
            ->assertSee('<a href="'.route('pages.show', $structure).'" rel="next"', false)
            // What a swipe on a phone follows.
            ->assertSee('data-swipe-prev="'.route('pages.show', $history).'"', false)
            ->assertSee('data-swipe-next="'.route('pages.show', $structure).'"', false)
            ->assertDontSee('Hidden');

        // The first has nothing before it, the last nothing after; a page in no group leads nowhere.
        $this->get(route('pages.show', $history))->assertDontSee('rel="prev"', false)->assertSee('rel="next"', false);
        $this->get(route('pages.show', $structure))->assertSee('rel="prev"', false)->assertDontSee('rel="next"', false);
        $this->get(route('pages.show', $alone))->assertOk()->assertDontSee('data-swipe', false);
    }

    public function test_a_page_that_is_not_out_is_seen_only_by_an_administrator(): void
    {
        SitePage::factory()->create(['slug' => 'draft', 'is_published' => false]);

        $this->get('/p/draft')->assertNotFound();
        $this->actingAs(User::factory()->create())->get('/p/draft')->assertNotFound();
        $this->actingAs(User::factory()->admin()->create())->get('/p/draft')->assertOk()->assertSee('Not published yet');
        $this->get('/p/no-such-page')->assertNotFound();
    }

    public function test_the_footer_of_every_page_links_to_the_pages_that_asked_for_it_in_order(): void
    {
        SitePage::factory()->create(['title' => 'Vision and mission', 'position' => 2]);
        SitePage::factory()->create(['title' => 'History', 'position' => 1]);
        SitePage::factory()->create(['title' => 'Not in the footer', 'show_in_footer' => false]);
        SitePage::factory()->create(['title' => 'Not out yet', 'is_published' => false]);

        $this->get('/')
            ->assertSeeInOrder(['History', 'Vision and mission'])
            ->assertDontSee('Not in the footer')
            ->assertDontSee('Not out yet');
    }

    public function test_the_footer_follows_a_change_at_once(): void
    {
        $page = SitePage::factory()->create(['title' => 'Old name']);
        $this->get('/')->assertSee('Old name');

        $page->update(['title' => 'New name']);
        $this->get('/')->assertSee('New name')->assertDontSee('Old name');

        $page->delete();
        $this->get('/')->assertDontSee('New name');
    }

    public function test_a_script_written_into_a_page_never_reaches_the_reader(): void
    {
        SitePage::factory()->create(['slug' => 'x', 'body' => '<p>Fine.</p><script>alert("b")</script><img src=x onerror="alert(1)">']);

        $page = $this->get('/p/x')->assertOk()->getContent();

        $this->assertStringNotContainsString('alert("b")', $page);
        $this->assertStringNotContainsString('onerror', $page);
    }

    public function test_a_reader_in_english_gets_the_english_where_there_is_some(): void
    {
        SitePage::factory()->create(['slug' => 'history', 'title' => 'ประวัติโรงพยาบาล', 'title_en' => 'Our history', 'body' => '<p>เนื้อหาไทย</p>', 'body_en' => '<p>English text</p>']);

        $this->withUnencryptedCookie(SetLocale::COOKIE, 'en')->get('/p/history')->assertSee('Our history')->assertSee('English text')->assertDontSee('เนื้อหาไทย');
        $this->withUnencryptedCookie(SetLocale::COOKIE, 'th')->get('/p/history')->assertSee('ประวัติโรงพยาบาล')->assertSee('เนื้อหาไทย');
    }

    public function test_an_administrator_writes_a_page(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateSitePage::class)
            ->fillForm(['title' => 'ประวัติโรงพยาบาล', 'slug' => 'history', 'body' => '<p>เปิดให้บริการ</p>'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->get('/p/history')->assertOk()->assertSee('ประวัติโรงพยาบาล');
    }

    public function test_the_address_must_be_plain_and_its_own(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        SitePage::factory()->create(['slug' => 'history']);

        foreach (['History Page', 'ประวัติ', '../admin', 'history'] as $slug) {
            Livewire::test(CreateSitePage::class)
                ->fillForm(['title' => 'A page', 'slug' => $slug, 'body' => '<p>Text.</p>'])
                ->call('create')
                ->assertHasFormErrors(['slug']);
        }

        $this->assertSame(0, SitePage::where('title', 'A page')->count());
    }

    public function test_a_new_site_has_outlines_for_the_hospital_to_write_and_shows_neither_until_it_does(): void
    {
        $guide = SitePage::where('slug', 'patient-guide')->firstOrFail();
        $donate = SitePage::where('slug', 'donate')->firstOrFail();

        $this->assertFalse($guide->is_published);
        $this->assertFalse($donate->is_published);
        // Headings saying what to write, and nothing made up under them.
        $this->assertStringContainsString('<h2>เอกสารที่ต้องนำมา</h2>', $guide->body);
        $this->assertStringContainsString('<h2>ช่องทางบริจาค</h2>', $donate->body);

        $this->get('/p/patient-guide')->assertNotFound();
        $this->get('/about')->assertDontSee('ขั้นตอนการรับบริการ');
        $this->get('/sitemap.xml')->assertDontSee('patient-guide');

        $this->actingAs(User::factory()->admin()->create())->get('/p/patient-guide')->assertOk()->assertSee('Not published yet');
    }

    public function test_an_administrator_takes_a_page_down(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $page = SitePage::factory()->create(['slug' => 'history']);

        Livewire::test(EditSitePage::class, ['record' => $page->slug])
            ->fillForm(['is_published' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        auth()->logout();
        $this->get('/p/history')->assertNotFound();
    }

    public function test_only_an_administrator_manages_pages_and_the_screen_reads_in_thai(): void
    {
        app()->setLocale('th');

        $this->actingAs(User::factory()->create())->get('/admin/site-pages')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/admin/site-pages')->assertOk()->assertSee('หน้าเนื้อหา');
    }
}
