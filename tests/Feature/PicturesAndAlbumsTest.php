<?php

namespace Tests\Feature;

use App\Filament\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Resources\BlogPosts\Pages\CreateBlogPost;
use App\Models\Announcement;
use App\Models\AnnouncementType;
use App\Models\Banner;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PicturesAndAlbumsTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_announcement_made_as_one_picture_shows_it_whole_whatever_its_shape(): void
    {
        $upright = Announcement::factory()->create(['title' => 'Dengue warning', 'image_path' => 'announcements/pictures/upright.webp']);
        $plain = Announcement::factory()->create(['title' => 'No picture']);

        $page = $this->get(route('announcements.show', $upright))->assertOk()->getContent();

        $this->assertStringContainsString('announcements/pictures/upright.webp', $page);
        $this->assertStringContainsString('alt="Dengue warning"', $page);
        // Never cropped: its own shape, within the height of the screen.
        $this->assertMatchesRegularExpression('/<img[^>]*upright\.webp[^>]*class="[^"]*max-h-\[85vh\][^"]*w-auto[^"]*max-w-full/', $page);
        $this->assertStringNotContainsString('object-cover', explode('<footer', explode('<article', $page)[1])[0]);
        // And it is what a shared link shows.
        $this->assertMatchesRegularExpression('/property="og:image" content="[^"]*upright\.webp"/', $page);

        $this->get(route('announcements.show', $plain))->assertOk()->assertDontSee('max-h-[85vh]', false);

        // A small copy in the lists, for the eye to catch.
        $this->get('/announcements')->assertSee('announcements/pictures/upright.webp', false);
        $this->get('/')->assertSee('announcements/pictures/upright.webp', false);
    }

    public function test_an_announcements_picture_can_be_among_the_slides_and_leads_to_the_announcement(): void
    {
        $this->showOnHome('slides');
        Banner::factory()->create(['title' => 'A slide of its own', 'image_path' => 'banners/own.webp']);
        $in = Announcement::factory()->create(['title' => 'Flu vaccine week', 'image_path' => 'announcements/pictures/flu.webp', 'in_slider' => true, 'ends_at' => now()->addDays(3)]);
        Announcement::factory()->create(['title' => 'Not asked for', 'image_path' => 'announcements/pictures/quiet.webp']);
        Announcement::factory()->create(['title' => 'Asked, but has no picture', 'in_slider' => true]);

        $slides = Banner::slides();

        $this->assertSame(['A slide of its own', 'Flu vaccine week'], $slides->pluck('title')->all());
        $this->assertSame(route('announcements.show', $in), $slides[1]->url);

        preg_match('/<ul class="slider-track.*?<\/ul>/s', $this->get('/')->getContent(), $track);

        $this->assertStringContainsString('<a href="'.route('announcements.show', $in).'" class="relative block', $track[0]);
        $this->assertStringContainsString('<img src="'.Storage::disk('public')->url('announcements/pictures/flu.webp').'" alt="Flu vaccine week"', $track[0]);
        $this->assertStringNotContainsString('quiet.webp', $track[0]);

        // When the announcement ends, its slide goes with it.
        $this->travel(4)->days();
        $this->assertSame(['A slide of its own'], Banner::slides()->pluck('title')->all());
    }

    public function test_an_author_posts_a_one_page_announcement_and_puts_it_in_the_slides(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateAnnouncement::class)
            ->fillForm([
                'title' => 'Dengue warning',
                'announcement_type_id' => AnnouncementType::where('slug', 'news')->value('id'),
                'image_path' => UploadedFile::fake()->image('onepage.jpg', 1240, 1754),
                'in_slider' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $announcement = Announcement::firstOrFail();

        $this->assertStringEndsWith('.webp', $announcement->image_path);
        Storage::disk('public')->assertExists($announcement->image_path);
        $this->assertTrue(Banner::slides()->contains('title', 'Dengue warning'));

        // The picture goes when it is replaced, and when the announcement does.
        $old = $announcement->image_path;
        Storage::disk('public')->put('announcements/pictures/new.webp', 'x');
        $announcement->update(['image_path' => 'announcements/pictures/new.webp']);
        Storage::disk('public')->assertMissing($old);

        $announcement->delete();
        Storage::disk('public')->assertMissing('announcements/pictures/new.webp');
    }

    public function test_an_article_about_an_activity_shows_its_album(): void
    {
        $post = BlogPost::factory()->create(['title' => 'Blood donation day', 'gallery' => ['blog/albums/a.webp', 'blog/albums/b.webp', 'blog/albums/c.webp']]);
        $plain = BlogPost::factory()->create();

        $this->get(route('blog.show', $post))
            ->assertOk()
            ->assertSee('3 photographs')
            ->assertSeeInOrder(['blog/albums/a.webp', 'blog/albums/b.webp', 'blog/albums/c.webp'], false)
            ->assertSee('alt="Photograph 2 of 3"', false);

        $this->get(route('blog.show', $plain))->assertOk()->assertDontSee('photograph');
    }

    public function test_an_author_writes_up_an_activity_with_its_photographs(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateBlogPost::class)
            ->fillForm([
                'title' => 'Blood donation day',
                'body' => '<p>Sixty donors came.</p>',
                'status' => BlogPost::STATUS_PUBLISHED,
                'gallery' => [UploadedFile::fake()->image('one.jpg', 2400, 1600), UploadedFile::fake()->image('two.jpg', 1600, 2400)],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = BlogPost::firstOrFail();

        $this->assertCount(2, $post->gallery);
        foreach ($post->gallery as $path) {
            $this->assertStringEndsWith('.webp', $path);
            Storage::disk('public')->assertExists($path);
        }

        // A photograph taken out of the album is removed; deleting the article removes the rest.
        [$kept, $dropped] = $post->gallery;
        $post->update(['gallery' => [$kept]]);
        Storage::disk('public')->assertMissing($dropped);

        $post->delete();
        Storage::disk('public')->assertMissing($kept);
    }

    public function test_only_pictures_are_taken_for_either(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());
        $svg = UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

        Livewire::test(CreateBlogPost::class)
            ->fillForm(['title' => 'A', 'body' => '<p>x</p>', 'gallery' => [$svg]])
            ->call('create')
            ->assertHasFormErrors(['gallery']);

        Livewire::test(CreateAnnouncement::class)
            ->fillForm(['title' => 'A', 'announcement_type_id' => AnnouncementType::where('slug', 'news')->value('id'), 'image_path' => $svg])
            ->call('create')
            ->assertHasFormErrors(['image_path']);
    }

    public function test_the_fields_read_in_thai(): void
    {
        app()->setLocale('th');
        $this->actingAs(User::factory()->create());

        $this->get('/admin/announcements/create')->assertSee('ภาพหน้าปก / ภาพประกาศ')->assertSee('แสดงภาพนี้ในสไลด์หน้าแรกด้วย');
        $this->get('/admin/blog-posts/create')->assertSee('อัลบั้มภาพ');
    }
}
