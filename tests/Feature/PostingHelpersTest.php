<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\Announcements\Pages\EditAnnouncement;
use App\Filament\Resources\Announcements\Pages\ListAnnouncements;
use App\Models\Announcement;
use App\Models\Department;
use App\Models\User;
use App\Support\Reports;
use Filament\Actions\ReplicateAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** The bell for posts of the departments, a preview, a copy, "this was useful", and the report by department. */
class PostingHelpersTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_post_sent_for_approval_rings_for_those_who_approve_and_not_for_the_sender(): void
    {
        $dental = Department::factory()->create(['name' => 'Dental']);
        $admin = User::factory()->create(['role' => Role::Admin]);
        $author = User::factory()->create(['role' => Role::Author]);
        $sender = User::factory()->create(['role' => Role::Author, 'department_id' => $dental->id]);
        $other = User::factory()->create(['role' => Role::Author, 'department_id' => Department::factory()->create()->id]);

        $this->actingAs($sender);
        Announcement::factory()->create(['title' => 'Brush twice a day', 'department_id' => $dental->id, 'status' => Announcement::STATUS_PENDING]);

        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame(1, $author->notifications()->count());
        $this->assertSame(0, $sender->notifications()->count());
        $this->assertSame(0, $other->notifications()->count());

        $notice = $admin->notifications()->first()->data;
        $this->assertSame('A post is waiting for approval', $notice['title']);
        $this->assertStringContainsString('Brush twice a day', $notice['body']);
        $this->assertStringContainsString('Dental', $notice['body']);
    }

    public function test_approving_and_sending_back_ring_for_the_department(): void
    {
        $dental = Department::factory()->create();
        $admin = User::factory()->create(['role' => Role::Admin]);
        $account = User::factory()->create(['role' => Role::Author, 'department_id' => $dental->id]);
        $post = Announcement::factory()->create(['title' => 'Brush twice a day', 'department_id' => $dental->id, 'status' => Announcement::STATUS_PENDING]);

        $this->actingAs($admin);
        $admin->notifications()->delete();

        $post->update(['status' => Announcement::STATUS_RETURNED, 'review_note' => 'Add the opening hours.']);
        $back = $account->notifications()->latest()->first()->data;
        $this->assertSame('A post was sent back for changes', $back['title']);
        $this->assertStringContainsString('Add the opening hours.', $back['body']);

        $post->update(['status' => Announcement::STATUS_PENDING]);
        $post->update(['status' => Announcement::STATUS_PUBLISHED]);
        $this->assertSame(['A post was approved', 'A post was sent back for changes'], $account->notifications()->get()->sortByDesc('created_at')->pluck('data.title')->sort()->values()->all());

        // The one who did it is not told; a change that leaves the status alone rings for nobody.
        $this->assertSame(0, $admin->notifications()->count());
        $post->update(['title' => 'Brush twice a day, properly']);
        $this->assertSame(2, $account->notifications()->count());
    }

    public function test_what_the_whole_hospital_publishes_rings_for_nobody(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin]);
        $this->actingAs(User::factory()->create(['role' => Role::Author]));

        Announcement::factory()->create();
        Announcement::factory()->create(['status' => Announcement::STATUS_DRAFT]);

        $this->assertSame(0, $admin->notifications()->count());
    }

    public function test_a_post_can_be_previewed_from_where_it_is_edited(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
        $draft = Announcement::factory()->create(['title' => 'Not out yet', 'status' => Announcement::STATUS_DRAFT]);

        Livewire::test(EditAnnouncement::class, ['record' => $draft->getRouteKey()])
            ->assertActionHasUrl('preview', route('announcements.show', $draft));

        $this->get(route('announcements.show', $draft))->assertOk()->assertSee('Not out yet')->assertSee('only staff who are signed in');
    }

    public function test_a_copy_is_a_draft_with_the_same_words_and_none_of_the_files(): void
    {
        $this->actingAs($admin = User::factory()->create(['role' => Role::Admin]));
        $first = Announcement::factory()->create([
            'title' => 'Procurement for October', 'body' => '<p>The list.</p>', 'is_pinned' => true, 'in_slider' => true,
            'image_path' => 'announcements/one.webp', 'attachments' => ['announcements/one.pdf'], 'views' => 40, 'helpful' => 3,
            'links' => [['label' => 'The plan', 'url' => 'https://drive.google.com/file/d/abc']],
        ]);

        Livewire::test(ListAnnouncements::class)->callAction(TestAction::make(ReplicateAction::class)->table($first));

        $copy = Announcement::whereKeyNot($first->id)->sole();

        $this->assertSame('Procurement for October (copy)', $copy->title);
        $this->assertSame('<p>The list.</p>', $copy->body);
        $this->assertSame(Announcement::STATUS_DRAFT, $copy->status);
        $this->assertSame($admin->id, $copy->author_id);
        $this->assertNotSame($first->slug, $copy->slug);
        $this->assertFalse($copy->is_pinned);
        $this->assertFalse($copy->in_slider);
        $this->assertNull($copy->image_path);
        $this->assertEmpty($copy->attachments);
        $this->assertSame(0, (int) $copy->views);
        $this->assertSame(0, (int) $copy->helpful);
        // Links to files kept elsewhere are words, and come along.
        $this->assertSame('The plan', $copy->links[0]['label']);
        // The first one is as it was.
        $this->assertSame('announcements/one.webp', $first->fresh()->image_path);
    }

    public function test_a_reader_says_an_announcement_was_useful_once(): void
    {
        $post = Announcement::factory()->create(['title' => 'Flu vaccine days']);
        $draft = Announcement::factory()->create(['status' => Announcement::STATUS_DRAFT]);
        $changed = $post->updated_at;

        $this->get(route('announcements.show', $post))->assertSee('Was this useful to you?')->assertSee('Yes, it was useful');

        $this->post(route('announcements.helpful', $post))->assertRedirect(route('announcements.show', $post).'#helpful');
        $this->post(route('announcements.helpful', $post));

        $this->assertSame(1, (int) $post->fresh()->helpful);
        // A reader's press is not a change to the announcement.
        $this->assertTrue($changed->equalTo($post->fresh()->updated_at));

        $this->get(route('announcements.show', $post))
            ->assertSee('Thank you for telling us.')
            ->assertSee('1 reader found this useful')
            ->assertDontSee('Yes, it was useful');

        // Another reader counts.
        $this->flushSession();
        $this->post(route('announcements.helpful', $post));
        $this->assertSame(2, (int) $post->fresh()->helpful);

        $this->post(route('announcements.helpful', $draft))->assertNotFound();
    }

    public function test_the_report_says_what_each_department_posted_and_how_it_was_read(): void
    {
        $dental = Department::factory()->create(['name' => 'Dental']);
        $pharmacy = Department::factory()->create(['name' => 'Pharmacy']);
        Department::factory()->create(['name' => 'Quiet unit']);

        Announcement::factory()->create(['department_id' => $dental->id, 'views' => 10, 'helpful' => 2, 'starts_at' => now()->subDay()]);
        Announcement::factory()->create(['department_id' => $dental->id, 'views' => 5, 'helpful' => 1, 'starts_at' => now()->subDays(2)]);
        Announcement::factory()->create(['department_id' => $pharmacy->id, 'views' => 7, 'starts_at' => now()->subDay()]);
        Announcement::factory()->create(['department_id' => $pharmacy->id, 'status' => Announcement::STATUS_DRAFT, 'starts_at' => now()->subDay()]);
        Announcement::factory()->create(['department_id' => $pharmacy->id, 'starts_at' => now()->subYears(2)]);

        $summary = Reports::summary(now()->subMonth(), now());

        $this->assertSame([
            ['name' => 'Dental', 'posts' => 2, 'views' => 15, 'helpful' => 3],
            ['name' => 'Pharmacy', 'posts' => 1, 'views' => 7, 'helpful' => 0],
        ], $summary['departments']);

        $this->assertContains(['Dental', 2, 15, 3], Reports::rows($summary, 'A month'));
        $this->assertStringContainsString('Posts of the departments', view('reports.body', ['summary' => $summary])->render());
    }
}
