<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Filament\Widgets\LatestPosts;
use App\Filament\Widgets\Welcome;
use App\Models\Announcement;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

/** The staff panel's own look: its welcome, the way back to the site, and the sign-in page. */
class PanelLookTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_first_page_greets_by_name_and_by_the_time_of_day(): void
    {
        $this->actingAs(User::factory()->create(['name' => 'Somsri', 'role' => Role::Admin]));

        $this->travelTo(Carbon::parse('2026-03-20 08:00', 'Asia/Bangkok'));
        Livewire::test(Welcome::class)->assertSee('Good morning, Somsri')->assertSee('Administrator');

        $this->travelTo(Carbon::parse('2026-03-20 14:00', 'Asia/Bangkok'));
        Livewire::test(Welcome::class)->assertSee('Good afternoon, Somsri');

        $this->travelTo(Carbon::parse('2026-03-20 19:00', 'Asia/Bangkok'));
        Livewire::test(Welcome::class)->assertSee('Good evening, Somsri');
    }

    public function test_the_first_thing_to_do_is_writing_a_post_and_waiting_posts_are_counted(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));

        Livewire::test(Welcome::class)
            ->assertSeeInOrder(['Write a new post', 'All announcements', 'Open the website', 'How to use the panel'])
            ->assertSee('href="'.AnnouncementResource::getUrl('create').'"', false)
            ->assertDontSee('Posts waiting for approval');

        Announcement::factory()->count(2)->create(['status' => Announcement::STATUS_PENDING, 'department_id' => Department::factory()->create()->id]);

        Livewire::test(Welcome::class)
            ->assertSee('Posts waiting for approval')
            ->assertSee('<span class="panel-door-count">2</span>', false)
            ->assertDontSee('All announcements');
    }

    public function test_the_account_of_a_department_is_shown_its_own_things(): void
    {
        $dental = Department::factory()->create(['name' => 'Dental']);
        $this->actingAs(User::factory()->create(['role' => Role::Author, 'department_id' => $dental->id]));
        Announcement::factory()->create(['status' => Announcement::STATUS_PENDING, 'department_id' => $dental->id]);

        Livewire::test(Welcome::class)
            ->assertSee('Author')
            ->assertSee('Dental')
            ->assertSeeInOrder(['Write a new post', 'My posts', 'The department\'s profile', 'See the page as visitors do'])
            ->assertSee('href="'.route('departments.show', $dental).'"', false)
            // What waits for approval is the reviewers' business.
            ->assertDontSee('Posts waiting for approval');
    }

    public function test_the_first_page_lists_the_posts_last_worked_on_a_department_its_own(): void
    {
        $dental = Department::factory()->create(['name' => 'Dental']);
        Announcement::factory()->create(['title' => 'Of the whole hospital']);
        $draft = Announcement::factory()->create(['title' => 'Brush twice a day', 'department_id' => $dental->id, 'status' => Announcement::STATUS_DRAFT]);

        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
        Livewire::test(LatestPosts::class)
            ->assertSee('Of the whole hospital')
            ->assertSee('Brush twice a day')
            ->assertSee('Draft')
            ->assertSee('href="'.AnnouncementResource::getUrl('edit', ['record' => $draft]).'"', false);

        $this->actingAs(User::factory()->create(['role' => Role::Author, 'department_id' => $dental->id]));
        Livewire::test(LatestPosts::class)->assertSee('Brush twice a day')->assertDontSee('Of the whole hospital');
    }

    public function test_the_panel_has_the_way_to_the_site_and_says_its_version(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));

        $this->get('/admin')
            ->assertOk()
            ->assertSee('class="panel-site-link"', false)
            ->assertSee('Open the website')
            ->assertSee('class="panel-version"', false)
            ->assertSee('IBM Plex Sans Thai');
    }

    public function test_the_sign_in_page_says_whom_it_is_for_and_has_the_way_out(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('For the staff of')
            ->assertSee('Back to the website')
            ->assertSee('Username or email');
    }
}
