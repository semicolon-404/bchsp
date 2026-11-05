<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Announcement;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The news list as an index: titles alone, by date, under a heading for each day, week, month, year or fiscal year. */
class NewsIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_titles_are_gathered_by_month_unless_the_reader_picks_another_way(): void
    {
        Announcement::factory()->create(['title' => 'November one', 'starts_at' => '2026-11-03 09:00']);
        Announcement::factory()->create(['title' => 'October one', 'starts_at' => '2026-10-20 09:00', 'is_pinned' => true]);
        Announcement::factory()->create(['title' => 'September one', 'starts_at' => '2026-09-15 09:00']);

        // By date alone, the pinned one in its own month; nothing set large and nothing beside it.
        $this->get('/announcements?view=index')->assertOk()
            ->assertSeeInOrder(['November 2026', 'November one', 'October 2026', 'October one', 'September 2026', 'September one'])
            ->assertDontSee('id="latest-heading"', false);

        // September is in the fiscal year before October's.
        $this->get('/announcements?view=index&by=fiscal')
            ->assertSeeInOrder(['Fiscal year 2570', 'November one', 'October one', 'Fiscal year 2569', 'September one']);

        $this->get('/announcements?view=index&by=year')->assertSee('2026')->assertDontSee('October 2026');
    }

    public function test_a_department_sees_its_own_on_the_site_from_the_panel(): void
    {
        $dental = Department::factory()->create();
        $this->actingAs(User::factory()->create(['role' => Role::Author, 'department_id' => $dental->id]));

        $this->get('/admin/announcements')->assertOk()
            ->assertSee(e(route('announcements.index', ['department' => $dental->slug, 'view' => 'index'])), false);
    }

    public function test_thirty_at_a_time_and_show_more_brings_the_rest(): void
    {
        Announcement::factory()->count(35)->create();

        $this->get('/announcements?view=index')->assertOk()
            ->assertSee('data-toc-more', false)->assertSee('(5)')
            ->assertDontSee('rel="next"', false);
        $this->assertSame(30, substr_count($this->get('/announcements?view=index')->getContent(), 'class="toc-row"'));

        // The next five, and no more to show.
        $next = $this->get('/announcements?view=index&page=2')->assertOk()->assertDontSee('data-toc-more', false)->getContent();
        $this->assertSame(5, substr_count($next, 'class="toc-row"'));
    }
}
