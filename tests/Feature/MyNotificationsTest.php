<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The bell in the site's bar: opening one marks it read, and the count goes down with it. */
class MyNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_one_marks_it_read_and_all_read_clears_the_count(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();

        foreach (['First', 'Second'] as $title) {
            Notification::make()->title($title)->actions([Action::make('open')->url('/admin/complaints')])->sendToDatabase($me);
        }
        Notification::make()->title('Not mine')->sendToDatabase($other);

        $this->actingAs($me);
        $this->get('/')->assertSee('account-badge', false)->assertSee('2</span>', false);

        $first = $me->notifications()->where('data->title', 'First')->first();
        $this->get(route('my-notifications.open', $first->id))->assertRedirect('/admin/complaints');
        $this->assertNotNull($first->fresh()->read_at);
        $this->assertSame(1, $me->unreadNotifications()->count());

        // Someone else's is not to be opened.
        $this->get(route('my-notifications.open', $other->notifications()->first()->id))->assertNotFound();

        $this->post(route('my-notifications.read'))->assertRedirect();
        $this->assertSame(0, $me->unreadNotifications()->count());
        $this->get('/')->assertDontSee('account-badge', false);
    }

    public function test_a_visitor_has_no_bell_and_cannot_reach_one(): void
    {
        $this->get('/')->assertDontSee('account-bell', false);
        $this->get('/me/notifications/x')->assertRedirect();
    }
}
