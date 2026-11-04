<?php

namespace Tests\Feature;

use App\Livewire\NoticePopups;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** What arrives for an account while the panel is open comes up at once, once, and not what was there before. */
class NoticePopupsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_what_arrives_after_the_page_was_opened_comes_up_and_only_once(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        Notification::make()->title('Old news')->sendToDatabase($user);
        $this->travel(1)->seconds();

        $popups = Livewire::test(NoticePopups::class);
        $popups->call('check')->assertNotNotified();

        $this->travel(1)->seconds();
        Notification::make()->title('A post waits for approval')->sendToDatabase($user);

        $popups->call('check')->assertNotified('A post waits for approval');

        session()->forget('filament.notifications');
        $popups->call('check')->assertNotNotified();
    }

    public function test_the_panel_asks_for_them(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertSeeLivewire(NoticePopups::class);
    }
}
