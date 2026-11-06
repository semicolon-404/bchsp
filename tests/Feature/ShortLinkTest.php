<?php

namespace Tests\Feature;

use App\Models\Announcement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Each announcement has a short address to share, /a/123, that leads to its full one. */
class ShortLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_short_address_leads_to_the_announcement_and_is_what_is_shared(): void
    {
        $one = Announcement::factory()->create(['title' => 'ประชาสัมพันธ์ จากกลุ่มงานประกันสุขภาพ']);

        $this->get('/a/'.$one->id)->assertStatus(301)->assertRedirect(route('announcements.show', $one));
        $this->get('/a/999999')->assertNotFound();

        $this->get(route('announcements.show', $one))->assertSee('data-share-url="'.url('/a/'.$one->id).'"', false)->assertSee('data-share-sheet', false);
    }
}
