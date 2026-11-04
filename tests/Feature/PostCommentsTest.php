<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\Announcements\Pages\EditAnnouncement;
use App\Filament\Resources\Announcements\RelationManagers\CommentsRelationManager;
use App\Models\Announcement;
use App\Models\AnnouncementComment;
use App\Models\Department;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/** The staff write to each other under a post, with pictures: those taking part hear of it, the public never sees it. */
class PostCommentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_comment_under_a_post_with_pictures_and_the_others_are_told(): void
    {
        Storage::fake('public');
        $dental = Department::factory()->create();
        $department = User::factory()->create(['role' => Role::Author, 'department_id' => $dental->id]);
        $admin = User::factory()->create(['role' => Role::Admin]);
        $post = Announcement::factory()->create(['department_id' => $dental->id, 'title' => 'Dental day']);

        $this->actingAs($admin);
        Livewire::test(CommentsRelationManager::class, ['ownerRecord' => $post, 'pageClass' => EditAnnouncement::class])
            ->callAction(TestAction::make(CreateAction::class)->table(), ['body' => 'Is the poster final?', 'images' => [UploadedFile::fake()->image('draft.jpg')]])
            ->assertHasNoActionErrors()
            ->assertSee('Is the poster final?');

        $comment = AnnouncementComment::sole();
        $this->assertSame($admin->id, $comment->user_id);
        $this->assertCount(1, $comment->images);
        Storage::disk('public')->assertExists($comment->images[0]);

        // The department hears of it; the one who wrote it does not.
        $this->assertSame(1, $department->notifications()->count());
        $this->assertSame(0, $admin->notifications()->count());

        // The department answers: the administrator, who commented before, hears of it.
        $this->actingAs($department);
        Livewire::test(CommentsRelationManager::class, ['ownerRecord' => $post, 'pageClass' => EditAnnouncement::class])
            ->callAction(TestAction::make(CreateAction::class)->table(), ['body' => 'Yes, final.'])
            ->assertHasNoActionErrors()
            // Only the writer of a comment, or an administrator, takes it back.
            ->assertActionHidden(TestAction::make(DeleteAction::class)->table($comment));
        $this->assertSame(1, $admin->notifications()->count());

        // A comment with nothing in it is not kept.
        Livewire::test(CommentsRelationManager::class, ['ownerRecord' => $post, 'pageClass' => EditAnnouncement::class])
            ->callAction(TestAction::make(CreateAction::class)->table(), ['body' => ''])
            ->assertHasActionErrors(['body']);
        $this->assertSame(2, AnnouncementComment::count());

        // Never on the public page.
        $this->get(route('announcements.show', $post))->assertOk()->assertDontSee('Is the poster final?');

        // Deleted, its pictures go with it.
        $this->actingAs($admin);
        Livewire::test(CommentsRelationManager::class, ['ownerRecord' => $post, 'pageClass' => EditAnnouncement::class])
            ->callAction(TestAction::make(DeleteAction::class)->table($comment));
        Storage::disk('public')->assertMissing($comment->images[0]);
    }
}
