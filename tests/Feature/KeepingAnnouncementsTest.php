<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\Announcements\Pages\ListAnnouncements;
use App\Filament\Widgets\NeedsAttention;
use App\Mail\MonthlyReport;
use App\Models\Announcement;
use App\Models\Department;
use App\Models\LinkCheck;
use App\Models\User;
use App\Support\LinkChecks;
use App\Support\PostNotices;
use App\Support\Reports;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/** The bin, several posts approved at once, links that no longer open, the ending bell and the month's report. */
class KeepingAnnouncementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_deleted_announcement_waits_in_the_bin_and_comes_back_whole(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('announcements/files/one.pdf', 'x');
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
        $post = Announcement::factory()->create(['title' => 'Flu vaccine days', 'attachments' => ['announcements/files/one.pdf']]);
        $address = route('announcements.show', $post);

        $post->delete();

        // Gone from the site, and from the panel's table until the bin is asked for.
        $this->get($address)->assertNotFound();
        $this->get('/announcements')->assertDontSee('Flu vaccine days');
        Livewire::test(ListAnnouncements::class)
            ->assertCanNotSeeTableRecords([$post])
            ->filterTable('trashed', false)
            ->assertCanSeeTableRecords([$post])
            ->callAction(TestAction::make(RestoreAction::class)->table($post));

        $this->assertFalse($post->fresh()->trashed());
        $this->get($address)->assertOk()->assertSee('Flu vaccine days');
        Storage::disk('public')->assertExists('announcements/files/one.pdf');

        // A new one with the same title while the first is in the bin takes another address.
        $post->delete();
        $again = Announcement::factory()->create(['title' => 'Flu vaccine days']);
        $this->assertNotSame($post->slug, $again->slug);
    }

    public function test_the_bin_is_emptied_after_thirty_days_and_can_be_emptied_by_hand(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('announcements/files/old.pdf', 'x');
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));

        $old = Announcement::factory()->create(['attachments' => ['announcements/files/old.pdf']]);
        $recent = Announcement::factory()->create();
        $kept = Announcement::factory()->create();
        $old->delete();
        $recent->delete();
        Announcement::withTrashed()->whereKey($old->id)->update(['deleted_at' => now()->subDays(31)]);

        $this->artisan('site:daily', ['--links' => 0])->assertSuccessful();

        $this->assertNull(Announcement::withTrashed()->find($old->id));
        $this->assertNotNull(Announcement::withTrashed()->find($recent->id));
        $this->assertNotNull($kept->fresh());
        Storage::disk('public')->assertMissing('announcements/files/old.pdf');

        Livewire::test(ListAnnouncements::class)
            ->filterTable('trashed', false)
            ->callAction(TestAction::make(ForceDeleteAction::class)->table($recent));
        $this->assertNull(Announcement::withTrashed()->find($recent->id));
    }

    public function test_several_posts_are_approved_at_once_and_only_those_that_wait(): void
    {
        $dental = Department::factory()->create();
        $account = User::factory()->create(['role' => Role::Author, 'department_id' => $dental->id]);
        $waiting = Announcement::factory()->count(2)->create(['department_id' => $dental->id, 'status' => Announcement::STATUS_PENDING]);
        $draft = Announcement::factory()->create(['department_id' => $dental->id, 'status' => Announcement::STATUS_DRAFT]);

        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
        Livewire::test(ListAnnouncements::class)
            ->selectTableRecords([...$waiting->all(), $draft])
            ->callAction(TestAction::make('approveMany')->table()->bulk());

        $this->assertSame([Announcement::STATUS_PUBLISHED, Announcement::STATUS_PUBLISHED], $waiting->map->fresh()->pluck('status')->all());
        $this->assertSame(Announcement::STATUS_DRAFT, $draft->fresh()->status);
        // The department hears of each.
        $this->assertSame(2, $account->notifications()->count());

        // The account of a department has no such button.
        $this->actingAs($account);
        Livewire::test(ListAnnouncements::class)->assertActionHidden(TestAction::make('approveMany')->table()->bulk());
    }

    public function test_several_posts_go_to_the_bin_at_once_and_come_back_or_go_for_good(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
        $chosen = Announcement::factory()->count(2)->create();
        $other = Announcement::factory()->create();

        Livewire::test(ListAnnouncements::class)
            ->selectTableRecords($chosen->all())
            ->callAction(TestAction::make(DeleteBulkAction::class)->table()->bulk());

        $this->assertSame(2, Announcement::onlyTrashed()->count());
        $this->assertNotNull($other->fresh());

        Livewire::test(ListAnnouncements::class)
            ->filterTable('trashed', false)
            ->selectTableRecords([$chosen[0]])
            ->callAction(TestAction::make(RestoreBulkAction::class)->table()->bulk());
        $this->assertFalse($chosen[0]->fresh()->trashed());

        Livewire::test(ListAnnouncements::class)
            ->filterTable('trashed', false)
            ->selectTableRecords([$chosen[1]])
            ->callAction(TestAction::make(ForceDeleteBulkAction::class)->table()->bulk());
        $this->assertNull(Announcement::withTrashed()->find($chosen[1]->id));

        // The account of a department empties nothing for good.
        $dental = Department::factory()->create();
        $this->actingAs(User::factory()->create(['role' => Role::Author, 'department_id' => $dental->id]));
        Livewire::test(ListAnnouncements::class)->assertActionHidden(TestAction::make(ForceDeleteBulkAction::class)->table()->bulk());
    }

    public function test_links_to_files_kept_elsewhere_are_looked_at_and_the_broken_ones_said(): void
    {
        Http::fake([
            'drive.google.com/file/d/gone*' => Http::response('', 404),
            'drive.google.com/file/d/closed*' => Http::response('', 403),
            'drive.google.com/file/d/slow*' => Http::response('', 503),
            '*' => Http::response('ok', 200),
        ]);
        $link = fn (string $id) => ['label' => $id, 'url' => 'https://drive.google.com/file/d/'.$id];

        $broken = Announcement::factory()->create(['title' => 'With a dead link', 'links' => [$link('gone'), $link('fine')]]);
        $closed = Announcement::factory()->create(['links' => [$link('closed')]]);
        $fine = Announcement::factory()->create(['links' => [$link('fine'), $link('slow')]]);
        Announcement::factory()->create(['status' => Announcement::STATUS_DRAFT, 'links' => [$link('gone')]]);

        $this->artisan('links:check')->assertSuccessful();

        $this->assertSame(5, LinkCheck::count());
        $this->assertSame(1, $broken->fresh()->brokenLinks());
        $this->assertSame(LinkCheck::PRIVATE, $closed->linkChecks()->sole()->state);
        // What could not be told is not called broken.
        $this->assertSame(0, $fine->fresh()->brokenLinks());
        $this->assertSame(2, LinkChecks::broken());

        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
        Livewire::test(NeedsAttention::class)->assertSee('2 announcements have links to files that do not open');
        Livewire::test(ListAnnouncements::class)
            ->assertSee('1 link does not open')
            ->filterTable('broken_links')
            ->assertCanSeeTableRecords([$broken, $closed])
            ->assertCanNotSeeTableRecords([$fine]);

        // The link put right, or taken off: nothing is said of it any more.
        $broken->update(['links' => [$link('fine')]]);
        $closed->update(['links' => null]);
        LinkChecks::run();
        $this->assertSame(0, LinkChecks::broken());
        $this->assertSame(3, LinkCheck::count());
    }

    public function test_the_author_is_told_once_that_an_announcement_is_about_to_end(): void
    {
        $author = User::factory()->create(['role' => Role::Author]);
        $ending = Announcement::factory()->create(['title' => 'Clinic closed for the holiday', 'author_id' => $author->id, 'ends_at' => now()->addDays(2)]);
        Announcement::factory()->create(['author_id' => $author->id, 'ends_at' => now()->addDays(20)]);
        Announcement::factory()->create(['author_id' => $author->id]);

        $this->assertSame(1, PostNotices::tellOfEndings());
        $this->assertSame(0, PostNotices::tellOfEndings());

        $notice = $author->notifications()->sole()->data;
        $this->assertSame('An announcement ends soon', $notice['title']);
        $this->assertStringContainsString('Clinic closed for the holiday', $notice['body']);

        // Given longer and coming near its end again, it is told of again.
        $ending->update(['ends_at' => now()->addDays(3)->subHour()]);
        $this->assertSame(1, PostNotices::tellOfEndings());
    }

    public function test_the_report_of_the_month_before_goes_to_the_administrators_on_the_first(): void
    {
        Mail::fake();
        User::factory()->create(['role' => Role::Admin, 'email' => 'admin@example.org']);
        User::factory()->create(['role' => Role::Author, 'email' => 'author@example.org']);

        $this->travelTo('2026-11-10 08:00');
        $this->artisan('report:monthly', ['--if-first' => true])->assertSuccessful();
        Mail::assertNothingSent();

        $this->travelTo('2026-11-01 08:00');
        $this->artisan('report:monthly', ['--if-first' => true])->assertSuccessful();

        Mail::assertSent(MonthlyReport::class, fn (MonthlyReport $mail) => $mail->hasTo('admin@example.org') && ! $mail->hasTo('author@example.org')
            && $mail->month->format('Y-m') === '2026-10' && $mail->summary['from']->format('Y-m-d') === '2026-10-01');

        $page = (new MonthlyReport(Reports::summary(now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()), now()->subMonth()->startOfMonth()))->render();
        $this->assertStringContainsString('October 2026', $page);
        $this->assertStringContainsString('Posts of the departments', $page);
        $this->assertStringContainsString('/admin/reports', $page);
    }
}
