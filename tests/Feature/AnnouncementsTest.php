<?php

namespace Tests\Feature;

use App\Filament\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Resources\Announcements\Pages\EditAnnouncement;
use App\Filament\Resources\Announcements\Pages\ListAnnouncements;
use App\Filament\Resources\AnnouncementTypes\Pages\ManageAnnouncementTypes;
use App\Http\Middleware\SetLocale;
use App\Models\Announcement;
use App\Models\AnnouncementType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AnnouncementsTest extends TestCase
{
    use RefreshDatabase;

    private function type(string $slug): AnnouncementType
    {
        return AnnouncementType::where('slug', $slug)->firstOrFail();
    }

    public function test_a_new_site_starts_with_the_usual_types(): void
    {
        $this->assertSame(['news', 'procurement', 'jobs', 'knowledge', 'activities'], AnnouncementType::orderBy('sort_order')->pluck('slug')->all());

        $this->get('/announcements')->assertOk()->assertSeeInOrder(['News', 'Procurement', 'Job openings', 'Health knowledge', 'Activities']);
    }

    public function test_the_list_shows_what_is_out_pinned_first_then_newest_with_the_ended_ones_kept(): void
    {
        Announcement::factory()->create(['title' => 'Older notice', 'starts_at' => now()->subDays(5)]);
        Announcement::factory()->create(['title' => 'Newer notice', 'starts_at' => now()->subDay()]);
        Announcement::factory()->pinned()->create(['title' => 'Pinned notice', 'starts_at' => now()->subDays(9)]);
        Announcement::factory()->scheduled()->create(['title' => 'Not yet']);
        // Pinned once, ended since: it keeps its place by date, no longer at the top.
        Announcement::factory()->pinned()->ended()->create(['title' => 'Over but kept']);

        $this->get('/announcements')
            ->assertOk()
            ->assertSee('4 announcements')
            ->assertSeeInOrder(['Pinned notice', 'Newer notice', 'Older notice', 'Over but kept'])
            ->assertDontSee('Not yet');

        // Only the one still in force wears the "pinned" mark.
        $this->assertSame(1, substr_count($this->get('/announcements')->getContent(), '>Pinned<'));
    }

    public function test_one_comes_out_by_itself_and_after_its_end_stays_in_the_archive(): void
    {
        $announcement = Announcement::factory()->create(['title' => 'Flu vaccine week', 'starts_at' => now()->addDay(), 'ends_at' => now()->addDays(8)]);

        $this->get('/announcements')->assertDontSee('Flu vaccine week');
        $this->get(route('announcements.show', $announcement))->assertNotFound();

        $this->travel(2)->days();
        $this->get('/announcements')->assertSee('Flu vaccine week');
        $this->get(route('announcements.show', $announcement))->assertOk()->assertDontSee('From the archive');

        $this->travel(7)->days();
        $this->get('/announcements')->assertSee('Flu vaccine week');
        $this->get(route('announcements.show', $announcement))->assertOk()->assertSee('From the archive: this announcement ended on');
    }

    public function test_a_fiscal_year_runs_from_october_to_september_in_the_buddhist_year(): void
    {
        $this->assertSame(2569, Announcement::fiscalYearOf(Carbon::parse('2025-10-01 00:00', 'Asia/Bangkok')));
        $this->assertSame(2569, Announcement::fiscalYearOf(Carbon::parse('2026-09-30 23:59', 'Asia/Bangkok')));
        $this->assertSame(2570, Announcement::fiscalYearOf(Carbon::parse('2026-10-01 00:00', 'Asia/Bangkok')));
        $this->assertSame(2568, Announcement::fiscalYearOf(Carbon::parse('2025-09-30 23:59', 'Asia/Bangkok')));
    }

    public function test_the_list_is_narrowed_to_today_this_week_this_month_or_dates_of_the_readers_own(): void
    {
        // A Sunday: the week began on Monday the 9th.
        $this->travelTo(Carbon::parse('2026-11-15 09:00', 'Asia/Bangkok'));

        Announcement::factory()->create(['title' => 'This morning', 'starts_at' => '2026-11-15 08:00']);
        Announcement::factory()->create(['title' => 'On Tuesday', 'starts_at' => '2026-11-10 09:00']);
        Announcement::factory()->create(['title' => 'Early this month', 'starts_at' => '2026-11-02 09:00']);
        Announcement::factory()->create(['title' => 'In October', 'starts_at' => '2026-10-20 09:00']);

        $this->get('/announcements')->assertSeeInOrder(['Any time', 'Today', 'This week', 'This month', 'Fiscal year', 'Choose the dates'])->assertSee('4 announcements');

        $this->get('/announcements?year=today')->assertSee('1 announcement')->assertSee('This morning')->assertDontSee('On Tuesday');
        $this->get('/announcements?year=week')->assertSee('2 announcements')->assertSee('On Tuesday')->assertDontSee('Early this month');
        $this->get('/announcements?year=month')->assertSee('3 announcements')->assertSee('Early this month')->assertDontSee('In October');

        // Both days are counted whole; the choice and the dates are kept from page to page and in the other choices.
        $this->get('/announcements?year=custom&from=2026-10-20&to=2026-11-02')
            ->assertSee('2 announcements')->assertSee('In October')->assertSee('Early this month')->assertDontSee('On Tuesday')
            ->assertSee('value="2026-10-20"', false)->assertSee('value="2026-11-02"', false)
            ->assertSee('year=custom&amp;from=2026-10-20&amp;to=2026-11-02&amp;view=cards', false);

        // The wrong way round is put right; one date alone is enough; nonsense is nothing.
        $this->get('/announcements?year=custom&from=2026-11-02&to=2026-10-20')->assertSee('2 announcements');
        $this->get('/announcements?year=custom&from=2026-11-10')->assertSee('2 announcements')->assertDontSee('Early this month');
        $this->get('/announcements?year=custom&from=2026-13-45&to=abc')->assertOk()->assertSee('4 announcements');
        $this->get('/announcements?year=custom&from[]=1')->assertOk()->assertSee('4 announcements');
    }

    public function test_the_list_is_narrowed_by_fiscal_year_and_each_year_says_how_many_it_holds(): void
    {
        $this->travelTo(Carbon::parse('2026-11-15 09:00', 'Asia/Bangkok'));

        Announcement::factory()->create(['title' => 'From this fiscal year', 'starts_at' => '2026-10-20 09:00']);
        Announcement::factory()->create(['title' => 'From September', 'starts_at' => '2026-09-30 09:00']);
        Announcement::factory()->create(['title' => 'From last December', 'starts_at' => '2025-12-01 09:00']);
        Announcement::factory()->create(['title' => 'From two years back', 'starts_at' => '2025-03-01 09:00']);
        Announcement::factory()->create(['title' => 'Not out yet', 'starts_at' => '2026-12-01 09:00']);

        $this->assertSame([2570 => 1, 2569 => 2, 2568 => 1], Announcement::fiscalYears());

        $this->get('/announcements')->assertSeeInOrder(['2570 (1)', '2569 (2)', '2568 (1)'])->assertSee('4 announcements');

        $this->get('/announcements?year=2569')
            ->assertOk()
            ->assertSee('2 announcements')
            ->assertSee('From September')
            ->assertSee('From last December')
            ->assertDontSee('From this fiscal year')
            ->assertDontSee('From two years back');

        // A year with nothing in it, or nonsense, shows everything.
        $this->get('/announcements?year=2500')->assertOk()->assertSee('4 announcements');
        $this->get('/announcements?year=abc')->assertOk()->assertSee('4 announcements');
        $this->get('/announcements?year[]=1')->assertOk();
    }

    public function test_type_year_and_a_word_in_the_title_narrow_the_list_together(): void
    {
        $this->travelTo(Carbon::parse('2026-11-15 09:00', 'Asia/Bangkok'));

        Announcement::factory()->for($this->type('procurement'), 'type')->create(['title' => 'ประกาศผู้ชนะ ซื้อยา มีนาคม', 'starts_at' => '2026-03-10 09:00']);
        Announcement::factory()->for($this->type('procurement'), 'type')->create(['title' => 'ประกาศผู้ชนะ ซื้อวัสดุทันตกรรม', 'starts_at' => '2026-03-12 09:00']);
        Announcement::factory()->for($this->type('procurement'), 'type')->create(['title' => 'ประกาศผู้ชนะ ซื้อยา ตุลาคม', 'starts_at' => '2026-10-12 09:00']);
        Announcement::factory()->for($this->type('news'), 'type')->create(['title' => 'รณรงค์ใช้ยาอย่างสมเหตุผล', 'starts_at' => '2026-03-15 09:00']);

        $this->get('/announcements?type=procurement&year=2569&q='.urlencode('ซื้อยา'))
            ->assertOk()
            ->assertSee('1 announcement')
            ->assertSee('ประกาศผู้ชนะ ซื้อยา มีนาคม')
            ->assertDontSee('ทันตกรรม')
            ->assertDontSee('ตุลาคม')
            ->assertDontSee('รณรงค์');

        // The years offered are those of the type being looked at.
        $this->assertSame([2570 => 1, 2569 => 2], Announcement::fiscalYears($this->type('procurement')->id));

        $this->get('/announcements?q='.urlencode('ไม่มีคำนี้'))->assertSee('No announcements match.');
        // The characters that mean "anything" to the database are looked for as themselves.
        $this->get('/announcements?q='.urlencode('%%'))->assertSee('No announcements match.');
    }

    public function test_the_filters_are_kept_from_page_to_page_and_when_the_type_is_changed(): void
    {
        Announcement::factory()->count(16)->for($this->type('news'), 'type')->create(['title' => 'Notice about vaccine']);

        $year = Announcement::fiscalYearOf(now());

        $this->get('/announcements?type=news&year='.$year.'&q=vaccine')
            ->assertSee('1 / 2')
            ->assertSee('type=news', false)
            ->assertSee('year='.$year, false)
            ->assertSee('q=vaccine', false)
            ->assertSee('page=2', false);
    }

    public function test_staff_who_are_signed_in_can_check_one_that_is_not_showing(): void
    {
        $announcement = Announcement::factory()->scheduled()->create();

        $this->actingAs(User::factory()->create())->get(route('announcements.show', $announcement))
            ->assertOk()
            ->assertSee('Not showing to the public right now');
    }

    public function test_each_type_wears_a_mark_of_its_own_in_the_list_the_chips_and_the_page(): void
    {
        $this->assertSame(['megaphone', 'clipboard-document-list', 'briefcase', 'heart', 'calendar-days'], AnnouncementType::orderBy('sort_order')->get()->map->icon()->all());
        $this->assertSame('var(--color-syn-string)', $this->type('procurement')->colour());

        $bid = Announcement::factory()->for($this->type('procurement'), 'type')->create(['title' => 'Winning bid']);
        Announcement::factory()->for($this->type('jobs'), 'type')->create(['title' => 'Hiring']);
        $orphan = Announcement::factory()->create(['title' => 'No type']);

        $list = $this->get('/announcements')->assertOk()->getContent();

        // The colour of each kind is on its mark and on its name; the name is always written out as well.
        $this->assertGreaterThanOrEqual(3, substr_count($list, 'var(--color-syn-string)'));
        $this->assertGreaterThanOrEqual(3, substr_count($list, 'var(--color-syn-variable)'));
        $this->assertStringContainsString('Procurement', $list);

        $this->get(route('announcements.show', $bid))->assertSee('var(--color-syn-string)', false);
        $this->get(route('announcements.show', $orphan))->assertOk();
    }

    public function test_a_mark_is_only_ever_one_of_the_symbols_and_colours_on_offer(): void
    {
        $type = AnnouncementType::factory()->create(['icon' => '../../x"><script>', 'tone' => 'red;background:url(x)']);

        $this->assertSame('megaphone', $type->icon());
        $this->assertSame('var(--color-brand)', $type->colour());

        Announcement::factory()->for($type, 'type')->create();
        $this->get('/announcements')->assertOk()->assertDontSee('url(x)', false);
    }

    public function test_an_administrator_gives_a_type_its_symbol_and_colour(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageAnnouncementTypes::class)
            ->callAction('create', ['name' => 'Training', 'icon' => 'academic-cap', 'tone' => 'green', 'sort_order' => 5])
            ->assertHasNoFormErrors();

        $type = AnnouncementType::where('name', 'Training')->firstOrFail();

        $this->assertSame('academic-cap', $type->icon());
        $this->assertSame('var(--color-syn-function)', $type->colour());

        Livewire::test(ManageAnnouncementTypes::class)
            ->callAction('create', ['name' => 'Bad', 'icon' => 'not-an-icon', 'tone' => 'green', 'sort_order' => 6])
            ->assertHasFormErrors(['icon']);
    }

    public function test_the_types_filter_the_list(): void
    {
        Announcement::factory()->for($this->type('procurement'), 'type')->create(['title' => 'Winning bid for office supplies']);
        Announcement::factory()->for($this->type('jobs'), 'type')->create(['title' => 'Hiring a pharmacist assistant']);

        $this->get('/announcements?type=procurement')
            ->assertOk()
            ->assertSee('Winning bid for office supplies')
            ->assertDontSee('Hiring a pharmacist assistant');

        $this->get('/announcements?type=no-such')->assertOk()->assertSee('Hiring a pharmacist assistant');
        $this->get('/announcements?type[]=x')->assertOk();
    }

    public function test_the_list_is_in_pages_of_fifteen(): void
    {
        Announcement::factory()->count(16)->create();

        $this->get('/announcements')->assertSee('1 / 2');
        $this->get('/announcements?page=2')->assertOk()->assertSee('rel="prev"', false);
    }

    public function test_an_announcement_shows_its_files_under_the_names_they_were_uploaded_with(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('announcements/files/abc123.pdf', str_repeat('x', 2048));
        $announcement = Announcement::factory()->for($this->type('procurement'), 'type')->create([
            'title' => 'Winning bid',
            'attachments' => ['announcements/files/abc123.pdf', 'announcements/files/gone.pdf'],
            'attachment_names' => ['announcements/files/abc123.pdf' => 'ประกาศผู้ชนะ มีนาคม 2569.pdf'],
        ]);

        $this->get(route('announcements.show', $announcement))
            ->assertOk()
            ->assertSee('Procurement')
            ->assertSee('ประกาศผู้ชนะ มีนาคม 2569.pdf')
            ->assertSee('announcements/files/abc123.pdf')
            ->assertSee('2 KB')
            // A file that is no longer there is not offered.
            ->assertDontSee('gone.pdf');

        $this->get('/announcements')->assertSee('Has files attached');
    }

    public function test_documents_kept_on_google_drive_are_linked_instead_of_uploaded(): void
    {
        $announcement = Announcement::factory()->create([
            'title' => 'Winning bid for medicines',
            'links' => [
                ['label' => 'ประกาศผู้ชนะ เดือนมีนาคม 2569', 'url' => 'https://drive.google.com/file/d/abc/view?usp=sharing'],
                ['label' => '', 'url' => 'https://drive.google.com/drive/folders/xyz'],
                ['label' => 'Not secure', 'url' => 'http://example.com/a.pdf'],
                ['label' => 'Not a link', 'url' => 'javascript:alert(1)'],
            ],
        ]);

        $this->assertCount(2, $announcement->documentLinks());

        $this->get(route('announcements.show', $announcement))
            ->assertOk()
            ->assertSee('ประกาศผู้ชนะ เดือนมีนาคม 2569')
            ->assertSee('href="https://drive.google.com/file/d/abc/view?usp=sharing" target="_blank" rel="noopener noreferrer"', false)
            // The reader is told where a press will take them; a link with no name is called by its site.
            ->assertSee('drive.google.com')
            ->assertDontSee('Not secure')
            ->assertDontSee('javascript:alert', false);

        $this->get('/announcements')->assertSee('Has files attached');
    }

    public function test_an_author_posts_a_procurement_notice_with_a_drive_link_and_no_file(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateAnnouncement::class)
            ->fillForm([
                'title' => 'Winning bid for dental supplies',
                'announcement_type_id' => $this->type('procurement')->id,
                'links' => [['label' => 'ประกาศผู้ชนะ', 'url' => 'drive.google.com/file/d/abc']],
            ])
            ->call('create')
            ->assertHasFormErrors();

        Livewire::test(CreateAnnouncement::class)
            ->fillForm([
                'title' => 'Winning bid for dental supplies',
                'announcement_type_id' => $this->type('procurement')->id,
                'links' => [['label' => 'ประกาศผู้ชนะ', 'url' => 'https://drive.google.com/file/d/abc/view']],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $announcement = Announcement::firstOrFail();

        $this->assertNull($announcement->attachments ?: null);
        $this->assertSame('https://drive.google.com/file/d/abc/view', $announcement->documentLinks()[0]['url']);
        $this->assertTrue($announcement->hasDocuments());
    }

    public function test_a_script_written_into_an_announcement_never_reaches_the_reader(): void
    {
        $announcement = Announcement::factory()->create([
            'title' => '<script>alert("t")</script>',
            'body' => '<p>Fine.</p><script>alert("b")</script>',
        ]);

        $page = $this->get(route('announcements.show', $announcement))->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert("t")', $page);
        $this->assertStringNotContainsString('alert("b")', $page);
        $this->assertStringNotContainsString('<script>alert("t")', $this->get('/announcements')->getContent());
    }

    public function test_the_list_can_be_looked_at_as_cards_with_covers_and_the_choice_is_kept(): void
    {
        Announcement::factory()->create(['title' => 'With a cover', 'image_path' => 'announcements/pictures/cover.webp', 'starts_at' => now()]);
        Announcement::factory()->count(16)->create(['starts_at' => now()->subDay()]);

        // Rows unless asked; anything else in the address is rows too.
        $this->get('/announcements')->assertOk()->assertDontSee('object-cover object-top', false)
            ->assertSee('href="'.route('announcements.index', ['view' => 'cards']).'"', false);
        $this->get('/announcements?view=nonsense')->assertOk()->assertDontSee('object-cover object-top', false);

        $this->get('/announcements?view=cards')
            ->assertOk()
            ->assertSee('announcements/pictures/cover.webp" alt="" loading="lazy" class="aspect-[4/3] w-full object-cover object-top"', false)
            // The types, the search and the next page all keep the cards.
            ->assertSee('href="'.e(route('announcements.index', ['type' => 'procurement', 'view' => 'cards'])).'"', false)
            ->assertSee('<input type="hidden" name="view" value="cards">', false)
            ->assertSee('view=cards&amp;page=2', false)
            // And the way back to rows.
            ->assertSee('href="'.route('announcements.index').'" class="chip gap-2"', false);
    }

    public function test_the_home_page_shows_the_five_at_the_top_of_the_list_and_the_way_to_the_rest(): void
    {
        $this->get('/')->assertDontSee('All announcements');

        foreach (range(1, 6) as $days) {
            Announcement::factory()->create(['title' => "Notice from $days days ago", 'starts_at' => now()->subDays($days)]);
        }

        $this->get('/')
            ->assertSeeInOrder(['<section id="news"', 'All announcements', 'Notice from 1 days ago', 'Notice from 5 days ago'], false)
            ->assertDontSee('Notice from 6 days ago')
            // The rest of the site is one list, each line leading to a part of it.
            ->assertSee('href="'.route('announcements.index', ['type' => 'procurement']).'" id="door-0"', false);
    }

    public function test_a_reader_in_english_gets_the_english_where_there_is_some_and_thai_dates_are_buddhist(): void
    {
        $announcement = Announcement::factory()->for($this->type('jobs'), 'type')->create([
            'title' => 'รับสมัครพนักงาน', 'title_en' => 'Hiring staff', 'starts_at' => '2026-10-01 09:00',
        ]);

        $this->withUnencryptedCookie(SetLocale::COOKIE, 'en')->get(route('announcements.show', $announcement))
            ->assertSee('Hiring staff')->assertSee('Job openings')->assertSee('1 Oct 2026');
        $this->withUnencryptedCookie(SetLocale::COOKIE, 'th')->get(route('announcements.show', $announcement))
            ->assertSee('รับสมัครพนักงาน')->assertSee('รับสมัครงาน')->assertSee('1 ต.ค. 2569');
    }

    public function test_an_author_posts_an_announcement_with_pdf_files(): void
    {
        Storage::fake('public');
        $this->actingAs($author = User::factory()->create());

        Livewire::test(CreateAnnouncement::class)
            ->fillForm([
                'title' => 'Winning bid for dental supplies',
                'announcement_type_id' => $this->type('procurement')->id,
                'is_pinned' => true,
                'attachments' => [UploadedFile::fake()->create('ประกาศผู้ชนะ.pdf', 300, 'application/pdf')],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $announcement = Announcement::firstOrFail();

        $this->assertTrue($announcement->author->is($author));
        $this->assertTrue($announcement->is_pinned);
        $this->assertTrue($announcement->isCurrent());
        $this->assertCount(1, $announcement->attachments);
        Storage::disk('public')->assertExists($announcement->attachments[0]);
        $this->assertSame('ประกาศผู้ชนะ.pdf', $announcement->files()[0]['name']);
        // Kept under a name that cannot be guessed from the upload.
        $this->assertStringNotContainsString('ประกาศ', $announcement->attachments[0]);
    }

    public function test_only_pdf_files_are_taken_and_a_title_and_type_are_needed(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateAnnouncement::class)
            ->fillForm(['title' => '', 'attachments' => [UploadedFile::fake()->create('macro.docm', 100, 'application/vnd.ms-word.document.macroEnabled.12')]])
            ->call('create')
            ->assertHasFormErrors(['title', 'announcement_type_id', 'attachments']);

        Livewire::test(CreateAnnouncement::class)
            ->fillForm(['title' => 'A page', 'announcement_type_id' => $this->type('news')->id, 'attachments' => [UploadedFile::fake()->create('page.html', 10, 'text/html')]])
            ->call('create')
            ->assertHasFormErrors(['attachments']);

        $this->assertSame(0, Announcement::count());
    }

    public function test_it_cannot_end_before_it_starts(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateAnnouncement::class)
            ->fillForm([
                'title' => 'Backwards', 'announcement_type_id' => $this->type('news')->id,
                'starts_at' => now()->addDays(3)->format('Y-m-d H:i:s'), 'ends_at' => now()->addDay()->format('Y-m-d H:i:s'),
            ])
            ->call('create')
            ->assertHasFormErrors(['ends_at']);
    }

    public function test_taking_a_file_off_or_deleting_the_announcement_removes_the_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('announcements/files/one.pdf', 'x');
        Storage::disk('public')->put('announcements/files/two.pdf', 'y');
        $announcement = Announcement::factory()->create(['attachments' => ['announcements/files/one.pdf', 'announcements/files/two.pdf']]);

        $announcement->update(['attachments' => ['announcements/files/two.pdf']]);
        Storage::disk('public')->assertMissing('announcements/files/one.pdf');
        Storage::disk('public')->assertExists('announcements/files/two.pdf');

        // In the bin it keeps its file, to come back whole; gone for good, the file goes with it.
        $announcement->delete();
        Storage::disk('public')->assertExists('announcements/files/two.pdf');

        $announcement->forceDelete();
        Storage::disk('public')->assertMissing('announcements/files/two.pdf');
    }

    public function test_an_author_unpins_and_ends_an_announcement(): void
    {
        $this->actingAs(User::factory()->create());
        $announcement = Announcement::factory()->pinned()->for($this->type('news'), 'type')->create();

        Livewire::test(EditAnnouncement::class, ['record' => $announcement->slug])
            ->fillForm(['is_pinned' => false, 'ends_at' => now()->subMinute()->format('Y-m-d H:i:s'), 'starts_at' => now()->subDays(2)->format('Y-m-d H:i:s')])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('In the archive', $announcement->fresh()->state());
        $this->assertFalse($announcement->fresh()->is_pinned);
    }

    public function test_the_panels_list_can_be_narrowed_to_a_fiscal_year(): void
    {
        $this->actingAs(User::factory()->create());
        $this->travelTo(Carbon::parse('2026-11-15 09:00', 'Asia/Bangkok'));
        $old = Announcement::factory()->create(['starts_at' => '2026-03-10 09:00']);
        $new = Announcement::factory()->create(['starts_at' => '2026-10-12 09:00']);

        Livewire::test(ListAnnouncements::class)
            ->filterTable('fiscal_year', 2569)
            ->assertCanSeeTableRecords([$old])
            ->assertCanNotSeeTableRecords([$new]);
    }

    public function test_only_an_administrator_arranges_the_types_and_deleting_one_keeps_its_announcements(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/announcement-types')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageAnnouncementTypes::class)
            ->callAction('create', ['name' => 'ประกาศทั่วไป', 'name_en' => 'General', 'sort_order' => 4])
            ->assertHasNoFormErrors();

        $type = AnnouncementType::where('name', 'ประกาศทั่วไป')->firstOrFail();
        $announcement = Announcement::factory()->for($type, 'type')->create();
        $type->delete();

        $this->assertNull($announcement->fresh()->announcement_type_id);
        $this->get('/announcements')->assertSee($announcement->title);
    }

    public function test_the_announcement_screens_open_for_an_author_and_read_in_thai(): void
    {
        app()->setLocale('th');
        $this->actingAs(User::factory()->create());

        $this->get('/admin/announcements')->assertOk()->assertSee('ประกาศ');
        $this->get('/admin/announcements/create')->assertOk()->assertSee('ปักหมุดไว้บนสุด')->assertSee('ไฟล์ PDF');
    }
}
