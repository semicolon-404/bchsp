<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Department;
use App\Support\Menus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** /posts: what the departments have posted, as a feed to read down. */
class DepartmentFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_feed_is_what_departments_posted_newest_first_and_nothing_else(): void
    {
        $dental = Department::factory()->create(['name' => 'Dental']);
        $pharmacy = Department::factory()->create(['name' => 'Pharmacy']);
        $hidden = Department::factory()->create(['name' => 'Hidden unit', 'is_active' => false]);

        $old = Announcement::factory()->create(['title' => 'Brush twice a day', 'department_id' => $dental->id, 'starts_at' => now()->subDays(5), 'body' => '<p>Morning and night.</p>', 'views' => 12]);
        Announcement::factory()->create(['title' => 'Medicine by post', 'department_id' => $pharmacy->id, 'starts_at' => now()->subDay()]);
        Announcement::factory()->create(['title' => 'Of the whole hospital']);
        Announcement::factory()->create(['title' => 'Of a hidden unit', 'department_id' => $hidden->id]);
        Announcement::factory()->create(['title' => 'A draft', 'department_id' => $dental->id, 'status' => Announcement::STATUS_DRAFT]);
        Announcement::factory()->create(['title' => 'Waiting for approval', 'department_id' => $dental->id, 'status' => Announcement::STATUS_PENDING]);

        $this->get('/posts')
            ->assertOk()
            ->assertSeeInOrder(['Medicine by post', 'Brush twice a day', 'Morning and night.', 'opened 12 times'])
            // Each post says who posted it, and leads to that department and to the announcement.
            ->assertSee('href="'.route('departments.show', $dental).'"', false)
            ->assertSee('href="'.route('announcements.show', $old).'"', false)
            ->assertDontSee('Of the whole hospital')
            ->assertDontSee('Of a hidden unit')
            ->assertDontSee('A draft')
            ->assertDontSee('Waiting for approval');
    }

    public function test_the_feed_is_narrowed_to_one_department(): void
    {
        $dental = Department::factory()->create(['name' => 'Dental', 'headline' => 'Care for teeth']);
        $pharmacy = Department::factory()->create(['name' => 'Pharmacy']);
        Announcement::factory()->count(2)->create(['department_id' => $dental->id]);
        Announcement::factory()->create(['title' => 'Medicine by post', 'department_id' => $pharmacy->id]);

        $this->get('/posts?department='.$dental->slug)
            ->assertOk()
            ->assertSee('<title>Posts from Dental · ', false)
            ->assertSee('Care for teeth')
            ->assertSee('See the profile')
            ->assertDontSee('Medicine by post');

        // Nonsense is the whole feed.
        $this->get('/posts?department=no-such')->assertOk()->assertSee('Medicine by post');
        $this->get('/posts?department[]=1')->assertOk();
    }

    public function test_the_feed_is_in_pages_of_ten_and_says_so_when_empty(): void
    {
        $this->get('/posts')->assertOk()->assertSee('No department has posted anything yet.');

        $dental = Department::factory()->create();
        Announcement::factory()->count(12)->create(['department_id' => $dental->id]);

        $this->get('/posts')->assertSee('1 / 2')->assertSee('rel="next"', false);
        $this->get('/posts?page=2')->assertOk()->assertSee('rel="prev"', false);
    }

    public function test_there_are_ways_in_once_a_department_has_posted_and_a_feed_for_one_department(): void
    {
        // Read afresh each time: the menus keep what one request worked out.
        $children = function () {
            Menus::forget();

            return array_column(collect(Menus::items('all'))->firstWhere('key', 'news')['children'], 'label');
        };

        $this->assertNotContains('Feed of the departments', $children());

        $dental = Department::factory()->create(['name' => 'Dental']);
        $other = Department::factory()->create();
        Announcement::factory()->create(['title' => 'Brush twice a day', 'department_id' => $dental->id]);
        Announcement::factory()->create(['title' => 'Of another', 'department_id' => $other->id]);

        $this->assertContains('Feed of the departments', $children());
        $this->get('/')->assertSee('href="'.route('posts').'"', false);
        $this->get('/sitemap.xml')->assertSee(route('posts'));
        $this->get(route('departments.show', $dental))->assertSee('href="'.route('feed', ['department' => $dental->slug]).'"', false);

        // The feed for a news program, of that department alone.
        $feed = simplexml_load_string($this->get('/feed?department='.$dental->slug)->assertOk()->getContent());

        $this->assertStringContainsString('Dental', (string) $feed->channel->title);
        $this->assertCount(1, $feed->channel->item);
        $this->assertSame('Brush twice a day', (string) $feed->channel->item[0]->title);
        $this->assertCount(2, simplexml_load_string($this->get('/feed')->getContent())->channel->item);
    }

    public function test_the_feed_reads_in_thai(): void
    {
        app()->setLocale('th');

        $this->get('/posts')->assertSee('ฟีดข่าว')->assertSee('ข่าวจากหน่วยงาน');
    }
}
