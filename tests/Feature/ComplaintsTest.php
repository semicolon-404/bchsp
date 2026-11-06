<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\Complaints\Pages\ManageComplaints;
use App\Filament\Widgets\NeedsAttention;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\User;
use App\Models\WorkGroup;
use App\Support\SiteSettings;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/** Complaints sent through the site: the form, the tracking number and code, following a matter, and the panel. */
class ComplaintsTest extends TestCase
{
    use RefreshDatabase;

    private function matter(array $over = []): array
    {
        return $over + [
            'kind' => 'service', 'title' => 'Waited two hours for medicine', 'detail' => 'At the pharmacy counter on Monday morning.',
            'who' => 'named', 'name' => 'Somsri Jaidee', 'phone' => '081-234-5678', 'consent' => '1',
        ];
    }

    public function test_the_pages_lead_to_the_form_and_to_following_a_matter(): void
    {
        SiteSettings::save(['contact.phone' => '044-579076', 'contact.emergency_phone' => '1669']);

        $this->get('/complaint')
            ->assertOk()
            ->assertSeeInOrder(['Complaints and suggestions', 'Three steps', 'Matters taken through this way', 'Good to know'])
            ->assertSee('href="'.route('complaint.create').'"', false)
            ->assertSee('href="'.route('complaint.track').'"', false)
            ->assertSee('href="tel:1669"', false)
            ->assertSee('15');

        $this->get('/complaint/send')
            ->assertOk()
            ->assertSeeInOrder(['What kind of matter is it?', 'Report of corruption or misconduct', 'What happened', 'Proof, if there is any', 'Who is sending it', 'Stay unnamed', 'Send the matter'])
            ->assertSee('enctype="multipart/form-data"', false);

        $this->get('/complaint/track')->assertOk()->assertSee('Look up my matter')->assertSee('What each state means')->assertDontSee('id="result"', false);
        // The page with the code is shown once, straight after sending, and not otherwise.
        $this->get('/complaint/sent')->assertRedirect(route('complaint'));

        $this->get('/contact')->assertSee('href="'.route('complaint').'"', false);
        $this->get('/sitemap.xml')->assertSee(route('complaint'))->assertSee(route('complaint.track'));
    }

    public function test_a_matter_is_sent_and_its_sender_is_given_a_number_and_a_code_once(): void
    {
        Storage::fake('local');
        $this->travelTo('2026-10-14 10:00');
        $admin = User::factory()->create(['role' => Role::Admin]);
        $group = WorkGroup::factory()->create(['name' => 'Pharmacy']);

        $response = $this->post('/complaint/send', $this->matter([
            'work_group_id' => $group->id, 'happened_on' => '2026-10-12', 'place' => 'Pharmacy counter', 'email' => 'somsri@example.org',
            'files' => [UploadedFile::fake()->image('queue.jpg'), UploadedFile::fake()->create('note.pdf', 200, 'application/pdf')],
        ]));

        $response->assertRedirect(route('complaint.sent'));
        $complaint = Complaint::sole();

        // "CMP-", the year of the Buddhist era in two digits, the month and day, and four letters and figures.
        $this->assertMatchesRegularExpression('/^CMP-691014-[A-HJ-NP-Z2-9]{4}$/', $complaint->code);
        $this->assertSame('received', $complaint->status);
        $this->assertSame('Somsri Jaidee', $complaint->name);
        $this->assertFalse($complaint->is_anonymous);
        $this->assertTrue($complaint->workGroup->is($group));

        // Who sent it cannot be read in the database itself; the code is kept only as a hash.
        $raw = DB::table('complaints')->first();
        $this->assertStringNotContainsString('Somsri', (string) $raw->name);
        $this->assertStringNotContainsString('081', (string) $raw->phone);
        $this->assertStringNotContainsString('example.org', (string) $raw->email);
        $this->assertDoesNotMatchRegularExpression('/^\d{6}$/', $raw->pin_hash);

        // What was attached is off the public disk, under names nobody can guess.
        $this->assertSame(['queue.jpg', 'note.pdf'], array_column($complaint->files(), 'name'));
        Storage::disk('local')->assertExists($complaint->files()[0]['path']);
        $this->assertStringStartsWith('complaints/'.$complaint->id.'/', $complaint->files()[0]['path']);
        $this->assertStringNotContainsString('queue', $complaint->files()[0]['path']);

        // The page that follows shows the number and the code; opened again, it has nothing to show.
        $given = session('complaint');
        $this->assertTrue($complaint->pinIs($given['pin']));
        $this->withSession(['complaint' => $given])->get('/complaint/sent')->assertOk()->assertSee($complaint->code)->assertSee($given['pin'])->assertSee('shown this once only');

        // The administrators hear of it.
        $this->assertSame('A new matter was sent in', $admin->notifications()->sole()->data['title']);
    }

    public function test_unnamed_means_nothing_of_the_sender_is_kept(): void
    {
        $this->post('/complaint/send', $this->matter(['who' => 'anonymous', 'name' => 'Typed before changing my mind', 'phone' => '0812345678', 'email' => 'a@example.org']))
            ->assertRedirect(route('complaint.sent'));

        $complaint = Complaint::sole();

        $this->assertTrue($complaint->is_anonymous);
        $this->assertNull($complaint->name);
        $this->assertNull($complaint->phone);
        $this->assertNull($complaint->email);
    }

    public function test_what_is_missing_or_wrong_is_said_and_nothing_is_kept(): void
    {
        // More is sent here in a moment than one address is allowed (see the test after this one).
        $this->withoutMiddleware(ThrottleRequests::class);

        $this->post('/complaint/send', [])->assertSessionHasErrors(['kind', 'title', 'detail', 'who', 'consent']);
        // Named: a name, and one way to reach them.
        $this->post('/complaint/send', $this->matter(['name' => '', 'phone' => '']))->assertSessionHasErrors(['name', 'phone']);
        $this->post('/complaint/send', $this->matter(['phone' => '', 'email' => 'somsri@example.org']))->assertSessionDoesntHaveErrors();
        Complaint::query()->delete();

        $this->post('/complaint/send', $this->matter(['kind' => 'gossip']))->assertSessionHasErrors('kind');
        $this->post('/complaint/send', $this->matter(['happened_on' => now()->addDay()->toDateString()]))->assertSessionHasErrors('happened_on');
        $this->post('/complaint/send', $this->matter(['consent' => '']))->assertSessionHasErrors('consent');
        $this->post('/complaint/send', $this->matter(['files' => [UploadedFile::fake()->create('run.exe', 10)]]))->assertSessionHasErrors('files.0');
        $this->post('/complaint/send', $this->matter(['files' => array_fill(0, 6, UploadedFile::fake()->image('a.jpg'))]))->assertSessionHasErrors('files');

        $this->assertSame(0, Complaint::count());

        // The field no person fills: a robot is sent on as if it had worked, and nothing is kept.
        $this->post('/complaint/send', $this->matter(['website' => 'https://spam.example']))->assertRedirect(route('complaint'));
        $this->assertSame(0, Complaint::count());
    }

    public function test_one_address_cannot_flood_the_form(): void
    {
        for ($i = 0; $i < 8; $i++) {
            $this->post('/complaint/send', $this->matter())->assertRedirect(route('complaint.sent'));
        }

        $this->post('/complaint/send', $this->matter())->assertStatus(429);
        $this->assertSame(8, Complaint::count());
    }

    public function test_a_matter_is_followed_with_its_number_and_code_and_says_nothing_of_who_sent_it(): void
    {
        $complaint = Complaint::factory()->create([
            'code' => 'CMP-691014-AB7K', 'title' => 'Waited two hours for medicine', 'is_anonymous' => false, 'name' => 'Somsri Jaidee', 'phone' => '0812345678',
            'status' => 'working', 'internal_note' => 'Spoke to the head of pharmacy.',
        ]);

        // However the number is typed.
        $this->post('/complaint/track', ['code' => ' cmp-691014-ab7k ', 'pin' => '123456'])
            ->assertOk()
            ->assertSeeInOrder(['id="result"', 'Waited two hours for medicine', 'CMP-691014-AB7K', 'Being dealt with', 'Nothing has been written back yet'], false)
            ->assertDontSee('Somsri')
            ->assertDontSee('0812345678')
            ->assertDontSee('Spoke to the head of pharmacy.');

        $complaint->update(['status' => 'done', 'reply' => 'A second counter now opens at busy hours.']);

        $this->post('/complaint/track', ['code' => 'CMP-691014-AB7K', 'pin' => '123456'])
            ->assertSeeInOrder(['Finished', 'What the hospital wrote back', 'A second counter now opens at busy hours.', 'Closed on']);
    }

    public function test_a_wrong_code_and_a_number_that_does_not_exist_are_answered_alike_and_tries_run_out(): void
    {
        Complaint::factory()->create(['code' => 'CMP-691014-AB7K']);

        $wrong = $this->from('/complaint/track')->post('/complaint/track', ['code' => 'CMP-691014-AB7K', 'pin' => '000000']);
        $none = $this->from('/complaint/track')->post('/complaint/track', ['code' => 'CMP-691014-ZZZZ', 'pin' => '123456']);

        $wrong->assertRedirect('/complaint/track')->assertSessionHasErrors(['code' => 'The tracking number or the code is not right.']);
        $none->assertSessionHasErrors(['code' => 'The tracking number or the code is not right.']);

        // Five wrong codes for one number, and even the right one must wait.
        for ($i = 0; $i < 4; $i++) {
            $this->post('/complaint/track', ['code' => 'CMP-691014-AB7K', 'pin' => '000000']);
        }

        $this->from('/complaint/track')->post('/complaint/track', ['code' => 'CMP-691014-AB7K', 'pin' => '123456'])
            ->assertRedirect('/complaint/track')->assertSessionHasErrors('code');

        $this->post('/complaint/track', ['code' => 'CMP-691014-AB7K', 'pin' => '12'])->assertSessionHasErrors('pin');
    }

    public function test_administrators_alone_read_a_matter_move_it_along_and_open_its_files(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('complaints/1/abc.pdf', 'proof');
        $complaint = Complaint::factory()->create([
            'title' => 'Waited two hours for medicine', 'is_anonymous' => false, 'name' => 'Somsri Jaidee',
            'attachments' => [['name' => 'note.pdf', 'path' => 'complaints/1/abc.pdf']],
        ]);
        $file = route('complaint.file', [$complaint, 0]);

        // Not a visitor, and not an author.
        $this->get($file)->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => Role::Author]));
        $this->get('/admin/complaints')->assertForbidden();
        $this->get($file)->assertForbidden();

        $this->actingAs($admin = User::factory()->create(['role' => Role::Admin]));
        $this->get('/admin/complaints')->assertOk();
        $this->get($file)->assertOk()->assertDownload('note.pdf');
        $this->get(route('complaint.file', [$complaint, 5]))->assertNotFound();

        Livewire::test(NeedsAttention::class)->assertSee('1 matter sent in has not been looked at');

        Livewire::test(ManageComplaints::class)
            ->assertCanSeeTableRecords([$complaint])
            ->callAction(TestAction::make('edit')->table($complaint), ['status' => 'done', 'reply' => 'A second counter now opens.', 'internal_note' => 'Checked with the head.'])
            ->assertHasNoFormErrors();

        $complaint->refresh();
        $this->assertSame('done', $complaint->status);
        $this->assertNotNull($complaint->closed_at);
        $this->assertTrue($complaint->handler->is($admin));
        // What the sender wrote is as it was.
        $this->assertSame('Waited two hours for medicine', $complaint->title);

        Livewire::test(NeedsAttention::class)->assertDontSee('has not been looked at');

        // Removed, its files go with it.
        $complaint->delete();
        Storage::disk('local')->assertMissing('complaints/1/abc.pdf');
    }

    public function test_a_department_deals_with_what_is_about_its_own_groups_without_learning_who_sent_it(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('complaints/9/x.pdf', 'proof');
        $dental = Department::factory()->create();
        $ours = Complaint::factory()->create([
            'title' => 'Long wait at the dental clinic', 'is_anonymous' => false, 'name' => 'Somsri Jaidee', 'phone' => '0812345678',
            'work_group_id' => WorkGroup::factory()->create(['department_id' => $dental->id])->id,
            'attachments' => [['name' => 'x.pdf', 'path' => 'complaints/9/x.pdf']],
        ]);
        $theirs = Complaint::factory()->create(['title' => 'Parking', 'work_group_id' => WorkGroup::factory()->create()->id]);
        $account = User::factory()->create(['role' => Role::Author, 'department_id' => $dental->id]);

        // Told when one comes in about it.
        $this->assertSame(0, $account->notifications()->count());
        Complaint::factory()->create(['work_group_id' => $ours->work_group_id]);
        $this->assertSame(1, $account->notifications()->count());

        $this->actingAs($account);
        $this->get('/admin/complaints')->assertOk();
        $this->get(route('complaint.file', [$ours, 0]))->assertOk();
        $this->get(route('complaint.file', [$theirs, 0]))->assertForbidden();

        Livewire::test(ManageComplaints::class)
            ->assertCanSeeTableRecords([$ours])
            ->assertCanNotSeeTableRecords([$theirs])
            ->mountAction(TestAction::make('edit')->table($ours))
            ->assertDontSee('Somsri Jaidee')
            ->assertDontSee('0812345678')
            ->callMountedAction()
            ->assertActionHidden(TestAction::make('delete')->table($ours));
    }

    public function test_a_matter_left_too_long_is_said_to_be_overdue(): void
    {
        SiteSettings::save(['complaint.days' => 10]);
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));

        $old = Complaint::factory()->create(['status' => 'checking', 'created_at' => now()->subDays(11)]);
        $closed = Complaint::factory()->create(['status' => 'done', 'created_at' => now()->subDays(30)]);

        $this->assertTrue($old->isOverdue());
        $this->assertFalse($closed->isOverdue());
        Livewire::test(NeedsAttention::class)->assertSee('1 matter is past the days it should be dealt with in');
    }
}
