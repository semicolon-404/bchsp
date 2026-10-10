<?php

namespace Tests\Feature;

use App\Filament\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Resources\Announcements\Pages\EditAnnouncement;
use App\Filament\Resources\AnnouncementTypes\Pages\ManageAnnouncementTypes;
use App\Http\Middleware\SetLocale;
use App\Models\Announcement;
use App\Models\AnnouncementType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    public function test_a_new_site_starts_with_the_three_usual_types(): void
    {
        $this->assertSame(['news', 'procurement', 'jobs'], AnnouncementType::orderBy('sort_order')->pluck('slug')->all());

        $this->get('/announcements')->assertOk()->assertSeeInOrder(['News', 'Procurement', 'Job openings']);
    }

    public function test_the_list_shows_what_is_inside_its_dates_pinned_first_then_newest(): void
    {
        Announcement::factory()->create(['title' => 'Older notice', 'starts_at' => now()->subDays(5)]);
        Announcement::factory()->create(['title' => 'Newer notice', 'starts_at' => now()->subDay()]);
        Announcement::factory()->pinned()->create(['title' => 'Pinned notice', 'starts_at' => now()->subDays(9)]);
        Announcement::factory()->scheduled()->create(['title' => 'Not yet']);
        Announcement::factory()->ended()->create(['title' => 'Over']);

        $this->get('/announcements')
            ->assertOk()
            ->assertSeeInOrder(['Pinned notice', 'Newer notice', 'Older notice'])
            ->assertDontSee('Not yet')
            ->assertDontSee('Over');
    }

    public function test_one_comes_out_and_takes_itself_down_by_itself(): void
    {
        $announcement = Announcement::factory()->create(['title' => 'Flu vaccine week', 'starts_at' => now()->addDay(), 'ends_at' => now()->addDays(8)]);

        $this->get('/announcements')->assertDontSee('Flu vaccine week');
        $this->get(route('announcements.show', $announcement))->assertNotFound();

        $this->travel(2)->days();
        $this->get('/announcements')->assertSee('Flu vaccine week');
        $this->get(route('announcements.show', $announcement))->assertOk();
        $this->get('/')->assertSee('Flu vaccine week');

        $this->travel(7)->days();
        $this->get('/announcements')->assertDontSee('Flu vaccine week');
        $this->get(route('announcements.show', $announcement))->assertNotFound();
    }

    public function test_staff_who_are_signed_in_can_check_one_that_is_not_showing(): void
    {
        $announcement = Announcement::factory()->scheduled()->create();

        $this->actingAs(User::factory()->create())->get(route('announcements.show', $announcement))
            ->assertOk()
            ->assertSee('Not showing to the public right now');
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

    public function test_the_home_page_shows_the_six_at_the_top_of_the_list(): void
    {
        $this->get('/')->assertDontSee('All announcements');

        foreach (range(1, 7) as $days) {
            Announcement::factory()->create(['title' => "Notice from $days days ago", 'starts_at' => now()->subDays($days)]);
        }

        $this->get('/')
            ->assertSee('Notice from 1 days ago')
            ->assertSee('Notice from 6 days ago')
            ->assertDontSee('Notice from 7 days ago')
            ->assertSee('All announcements');
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

        $announcement->delete();
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

        $this->assertSame('Ended', $announcement->fresh()->state());
        $this->assertFalse($announcement->fresh()->is_pinned);
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
