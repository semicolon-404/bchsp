<?php

namespace Tests\Feature;

use App\Filament\Pages\Notifications;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Every notification of the one signed in, on a page of its own: narrowed, searched, read and cleared. */
class NotificationsPageTest extends TestCase
{
    use RefreshDatabase;

    private function tell(User $user, string $title, string $icon, string $url): void
    {
        Notification::make()->title($title)->icon($icon)->actions([Action::make('open')->url($url)])->sendToDatabase($user);
    }

    public function test_one_sees_their_own_narrowed_searched_and_marks_and_clears_them(): void
    {
        $me = User::factory()->admin()->create();
        $other = User::factory()->admin()->create();
        $this->tell($me, 'A complaint came in', 'heroicon-o-chat-bubble-left-right', url('/admin/complaints'));
        $this->tell($me, 'Waiting for approval', 'heroicon-o-clock', url('/admin/announcements/1/edit'));
        $this->tell($other, 'Not mine', 'heroicon-o-clock', url('/admin/announcements'));

        $this->actingAs($me);
        $this->assertSame('2', Notifications::getNavigationBadge());

        Livewire::test(Notifications::class)
            ->assertSee('A complaint came in')->assertSee('Waiting for approval')->assertDontSee('Not mine')
            ->set('show', 'complaints')->assertSee('A complaint came in')->assertDontSee('Waiting for approval')
            ->set('show', 'posts')->assertSee('Waiting for approval')->assertDontSee('A complaint came in')
            ->set('show', 'all')->set('q', 'approval')->assertSee('Waiting for approval')->assertDontSee('A complaint came in')
            ->call('markAllRead');

        $this->assertSame(0, $me->unreadNotifications()->count());
        $this->assertSame(1, $other->unreadNotifications()->count());

        Livewire::test(Notifications::class)->call('clearRead');
        $this->assertSame(0, $me->notifications()->count());
        $this->assertSame(1, $other->notifications()->count());
    }

    public function test_opening_one_marks_it_read_and_goes_to_it_on_this_site_only(): void
    {
        $me = User::factory()->admin()->create();
        $this->tell($me, 'Here', 'heroicon-o-clock', 'https://elsewhere.example/admin/complaints?x=1');
        $id = $me->notifications()->value('id');

        $this->actingAs($me);
        Livewire::test(Notifications::class)->call('open', $id)->assertRedirect(url('/admin/complaints?x=1'));
        $this->assertNotNull($me->notifications()->first()->read_at);
    }
}
