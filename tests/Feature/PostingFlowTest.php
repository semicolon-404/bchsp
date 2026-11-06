<?php

namespace Tests\Feature;

use App\Filament\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Resources\Announcements\Pages\EditAnnouncement;
use App\Filament\Resources\Announcements\Pages\ListAnnouncements;
use App\Filament\Widgets\DepartmentStats;
use App\Filament\Widgets\NeedsAttention;
use App\Models\Announcement;
use App\Models\AnnouncementType;
use App\Models\Department;
use App\Models\User;
use App\Support\SiteSettings;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/** How a post gets from being written to being read: drafts, approval, pinning on a department's page, and passing it on. */
class PostingFlowTest extends TestCase
{
    use RefreshDatabase;

    private Department $dental;

    private User $desk;

    private int $news;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dental = Department::factory()->create(['name' => 'Dental']);
        $this->desk = User::factory()->create(['name' => 'Dental desk', 'department_id' => $this->dental->id]);
        $this->news = AnnouncementType::where('slug', 'news')->value('id');
    }

    public function test_a_draft_is_seen_by_staff_only_whatever_its_dates(): void
    {
        $draft = Announcement::factory()->create(['title' => 'Still being written', 'status' => Announcement::STATUS_DRAFT, 'image_path' => 'announcements/pictures/d.webp', 'in_slider' => true, 'is_popup' => true]);

        $this->assertFalse($draft->isPublished());
        $this->assertSame('Draft', $draft->state());
        $this->assertSame(0, Announcement::published()->count() + Announcement::current()->count() + Announcement::slides()->count());
        $this->assertNull(Announcement::popup());

        $this->get('/')->assertOk()->assertDontSee('Still being written');
        $this->get('/announcements')->assertDontSee('Still being written');
        $this->get('/search?q=written')->assertDontSee('Still being written');
        $this->get('/sitemap.xml')->assertDontSee($draft->slug);
        $this->get(route('announcements.show', $draft))->assertNotFound();

        // Staff read it to check it, and are told the public cannot. Nothing invites passing it on.
        $this->actingAs(User::factory()->create())->get(route('announcements.show', $draft))->assertOk()->assertSee('Not showing to the public right now')->assertDontSee('lineit/share', false);
    }

    public function test_an_author_keeps_a_draft_and_publishes_it_later(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateAnnouncement::class)
            ->assertFormSet(['status' => Announcement::STATUS_PUBLISHED])
            ->fillForm(['title' => 'Flu vaccine week', 'announcement_type_id' => $this->news, 'status' => Announcement::STATUS_DRAFT])
            ->call('create')
            ->assertHasNoFormErrors();

        $announcement = Announcement::firstOrFail();
        $this->assertSame(Announcement::STATUS_DRAFT, $announcement->status);

        Livewire::test(EditAnnouncement::class, ['record' => $announcement->getRouteKey()])
            ->fillForm(['status' => Announcement::STATUS_PUBLISHED])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($announcement->fresh()->isPublished());
    }

    public function test_what_a_departments_account_posts_waits_for_approval(): void
    {
        $this->actingAs($this->desk);

        // It cannot put its own writing out: that is not among what it can choose.
        Livewire::test(CreateAnnouncement::class)
            ->fillForm(['title' => 'Dental clinic closed on Friday', 'announcement_type_id' => $this->news])
            ->set('data.status', Announcement::STATUS_PUBLISHED)
            ->call('create')
            ->assertHasFormErrors(['status']);

        $this->assertSame(0, Announcement::count());

        Livewire::test(CreateAnnouncement::class)
            ->assertFormSet(['status' => Announcement::STATUS_PENDING])
            ->fillForm(['title' => 'Dental clinic closed on Friday', 'announcement_type_id' => $this->news])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Announcement::firstOrFail();

        $this->assertSame(Announcement::STATUS_PENDING, $post->status);
        $this->assertSame('Waiting for approval', $post->state());
        $this->assertSame($this->dental->id, $post->department_id);

        Livewire::test(ListAnnouncements::class)->assertActionHidden(TestAction::make('approve')->table($post));
        Livewire::test(DepartmentStats::class)->assertSee('Waiting for approval');

        auth()->logout();
        $this->get('/announcements')->assertDontSee('Dental clinic closed on Friday');
        $this->get(route('departments.show', $this->dental))->assertDontSee('Dental clinic closed on Friday');

        // Someone who posts for the whole hospital is told, and lets it out.
        $this->actingAs(User::factory()->create());

        Livewire::test(NeedsAttention::class)->assertSee('1 post from a department is waiting for approval');
        Livewire::test(ListAnnouncements::class)->callAction(TestAction::make('approve')->table($post));

        $this->assertTrue($post->fresh()->isPublished());

        auth()->logout();
        $this->get(route('departments.show', $this->dental))->assertSee('Dental clinic closed on Friday');

        // A change by the department is looked at again.
        $this->actingAs($this->desk);

        Livewire::test(EditAnnouncement::class, ['record' => $post->getRouteKey()])
            ->assertFormSet(['status' => Announcement::STATUS_PENDING])
            ->fillForm(['title' => 'Dental clinic closed all week'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(Announcement::STATUS_PENDING, $post->fresh()->status);

        // And it can still keep a draft to itself.
        Livewire::test(EditAnnouncement::class, ['record' => $post->getRouteKey()])->fillForm(['status' => Announcement::STATUS_DRAFT])->call('save');
        $this->assertSame(Announcement::STATUS_DRAFT, $post->fresh()->status);
    }

    public function test_a_post_is_sent_back_with_a_note_and_the_department_sends_it_again(): void
    {
        $post = Announcement::factory()->create(['title' => 'Dental health day', 'department_id' => $this->dental->id, 'announcement_type_id' => $this->news, 'status' => Announcement::STATUS_PENDING]);

        $this->actingAs(User::factory()->create());

        // A reason is asked for: a post sent back in silence helps nobody.
        Livewire::test(ListAnnouncements::class)->callAction(TestAction::make('sendBack')->table($post), ['review_note' => ''])->assertHasFormErrors(['review_note']);

        Livewire::test(ListAnnouncements::class)
            ->callAction(TestAction::make('sendBack')->table($post), ['review_note' => 'A patient can be recognised in the second photograph.'])
            ->assertHasNoFormErrors();

        $this->assertSame(Announcement::STATUS_RETURNED, $post->fresh()->status);
        $this->assertSame('Sent back', $post->fresh()->state());
        $this->assertFalse($post->fresh()->isPublished());

        // The department is told on its first page, reads the note on the post, changes it and sends it again.
        $this->actingAs($this->desk);

        Livewire::test(NeedsAttention::class)->assertSee('1 post was sent back for changes')->assertDontSee('ITA');
        Livewire::test(DepartmentStats::class)->assertSee('Sent back: 1');
        Livewire::test(ListAnnouncements::class)->assertActionHidden(TestAction::make('sendBack')->table($post));

        Livewire::test(EditAnnouncement::class, ['record' => $post->getRouteKey()])
            ->assertFormSet(['review_note' => 'A patient can be recognised in the second photograph.', 'status' => Announcement::STATUS_PENDING])
            ->assertFormFieldDisabled('review_note')
            ->fillForm(['title' => 'Dental health day, with other photographs'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(Announcement::STATUS_PENDING, $post->fresh()->status);
        // The note stays until it is approved, so the reviewer sees what was asked.
        $this->assertNotNull($post->fresh()->review_note);

        $this->actingAs(User::factory()->create());
        Livewire::test(ListAnnouncements::class)->callAction(TestAction::make('approve')->table($post));

        $this->assertTrue($post->fresh()->isPublished());
        $this->assertNull($post->fresh()->review_note);
    }

    public function test_the_guide_tells_each_reader_what_is_theirs_to_do(): void
    {
        $this->actingAs($this->desk)->get('/admin/guide')
            ->assertOk()
            ->assertSee('บัญชีของหน่วยงาน Dental')
            ->assertSee('หน้าโปรไฟล์ของหน่วยงาน')
            ->assertSee('ส่งให้ตรวจ')
            ->assertDontSee('ตรวจเรื่องจากหน่วยงาน');

        $this->actingAs(User::factory()->create())->get('/admin/guide')
            ->assertOk()
            ->assertSee('ตรวจเรื่องจากหน่วยงาน')
            ->assertSee('ส่งกลับแก้ไข')
            ->assertDontSee('หน้าโปรไฟล์ของหน่วยงาน');

        $this->actingAs(User::factory()->admin()->create())->get('/admin/guide')->assertOk()->assertSee('ผู้ดูแลระบบ');
    }

    public function test_the_hospital_can_let_departments_post_without_approval(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        SiteSettings::save(['bchsp.security.department_approval' => false]);

        $this->assertFalse(Announcement::waitsForApproval($this->desk));

        $this->actingAs($this->desk);

        Livewire::test(CreateAnnouncement::class)
            ->assertFormSet(['status' => Announcement::STATUS_PUBLISHED])
            ->fillForm(['title' => 'Out at once', 'announcement_type_id' => $this->news])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertTrue(Announcement::firstOrFail()->isPublished());
    }

    public function test_a_department_keeps_one_post_at_the_top_of_its_page(): void
    {
        $old = Announcement::factory()->create(['title' => 'Mobile unit timetable', 'department_id' => $this->dental->id, 'starts_at' => now()->subMonth(), 'pinned_in_department' => true]);
        $new = Announcement::factory()->create(['title' => 'Something newer', 'department_id' => $this->dental->id, 'announcement_type_id' => $this->news]);
        $central = Announcement::factory()->create(['title' => 'Of no department', 'pinned_in_department' => true]);

        // Only what belongs to a department can be pinned to one.
        $this->assertFalse($central->fresh()->pinned_in_department);

        $this->get(route('departments.show', $this->dental))->assertSeeInOrder(['Pinned', 'Mobile unit timetable', 'Something newer']);
        // On the list of the whole hospital it takes no place of its own.
        $this->get('/announcements')->assertSeeInOrder(['Something newer', 'Mobile unit timetable']);

        // Pinning another lets go of the first. The department's own account can do it.
        config(['bchsp.security.department_approval' => false]);
        $this->actingAs($this->desk);

        Livewire::test(EditAnnouncement::class, ['record' => $new->getRouteKey()])
            ->assertFormFieldVisible('pinned_in_department')
            ->assertFormFieldHidden('is_pinned')
            ->fillForm(['pinned_in_department' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($new->fresh()->pinned_in_department);
        $this->assertFalse($old->fresh()->pinned_in_department);
    }

    public function test_an_announcement_can_be_passed_on_and_its_pdf_read_in_place(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('announcements/files/notice.pdf', '%PDF-1.4');

        $announcement = Announcement::factory()->create(['title' => 'Flu vaccine week', 'attachments' => ['announcements/files/notice.pdf'], 'attachment_names' => ['announcements/files/notice.pdf' => 'Notice.pdf']]);
        $address = route('announcements.show', $announcement);

        $this->get($address)
            ->assertOk()
            // One Share button, its short address, opening the window with LINE, Facebook and the address to copy.
            ->assertSee('data-share-url="'.$announcement->shortUrl().'"', false)
            ->assertSee('data-share-to="line"', false)->assertSee('data-share-to="facebook"', false)
            ->assertSeeInOrder(['Notice.pdf', 'Read it here', '<iframe src="'.Storage::disk('public')->url('announcements/files/notice.pdf').'#view=FitH"'], false);

        // One with no file has nothing to read in place.
        $this->get(route('announcements.show', Announcement::factory()->create()))->assertOk()->assertDontSee('<iframe', false);
    }

    public function test_the_home_page_leads_to_the_departments_that_posted_lately(): void
    {
        $pharmacy = Department::factory()->create(['name' => 'Pharmacy']);
        $quiet = Department::factory()->create(['name' => 'Records']);
        $hidden = Department::factory()->create(['name' => 'Hidden unit', 'is_active' => false]);

        Announcement::factory()->create(['department_id' => $this->dental->id, 'starts_at' => now()->subDays(3)]);
        Announcement::factory()->create(['department_id' => $pharmacy->id, 'starts_at' => now()->subDay()]);
        Announcement::factory()->create(['department_id' => $hidden->id]);
        Announcement::factory()->create(['department_id' => $quiet->id, 'status' => Announcement::STATUS_DRAFT]);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['From the departments', e(route('posts', ['department' => $pharmacy->slug])), 'Pharmacy', e(route('posts', ['department' => $this->dental->slug])), 'Dental'], false)
            ->assertDontSee(route('departments.show', $quiet), false)
            ->assertDontSee('Hidden unit');

        // With none, the row takes no room.
        Announcement::query()->update(['department_id' => null]);
        $this->get('/')->assertDontSee('From the departments');
    }

    public function test_a_departments_account_sees_how_its_own_posts_are_doing(): void
    {
        Announcement::factory()->create(['title' => 'Brush twice a day', 'department_id' => $this->dental->id, 'views' => 120]);
        Announcement::factory()->create(['department_id' => $this->dental->id, 'views' => 30]);
        Announcement::factory()->create(['title' => 'Of another', 'views' => 9999]);

        $this->assertFalse(DepartmentStats::canView());

        $this->actingAs($this->desk);

        $this->assertTrue(DepartmentStats::canView());
        Livewire::test(DepartmentStats::class)->assertSee('150')->assertSee('Most read: Brush twice a day')->assertDontSee('9,999');
        $this->get('/admin')->assertOk()->assertSee('Dental');

        $this->actingAs(User::factory()->create());
        $this->assertFalse(DepartmentStats::canView());
    }

    public function test_the_new_words_read_in_thai(): void
    {
        app()->setLocale('th');

        $announcement = Announcement::factory()->create();

        $this->get(route('announcements.show', $announcement))->assertSee('แชร์')->assertSee('คัดลอกลิงก์');
        $this->actingAs($this->desk)->get('/admin/announcements/create')->assertSee('ส่งให้ตรวจ')->assertSee('ปักหมุดไว้บนสุดของหน้าหน่วยงาน');
    }
}
