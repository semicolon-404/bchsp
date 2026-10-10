<?php

namespace Tests\Feature;

use App\Filament\Resources\BlogCategories\Pages\ManageBlogCategories;
use App\Filament\Resources\BlogPosts\Pages\CreateBlogPost;
use App\Filament\Resources\BlogPosts\Pages\EditBlogPost;
use App\Http\Middleware\SetLocale;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_blog_lists_what_is_out_newest_first(): void
    {
        BlogPost::factory()->create(['title' => 'Older article', 'published_at' => now()->subDays(5)]);
        BlogPost::factory()->create(['title' => 'Newer article', 'published_at' => now()->subDay()]);

        $this->get('/blog')->assertOk()->assertSeeInOrder(['Newer article', 'Older article']);
    }

    public function test_drafts_and_scheduled_articles_are_not_shown_or_reachable(): void
    {
        $draft = BlogPost::factory()->draft()->create(['title' => 'Still a draft']);
        $scheduled = BlogPost::factory()->scheduled()->create(['title' => 'Out tomorrow']);

        $this->get('/blog')->assertDontSee('Still a draft')->assertDontSee('Out tomorrow');
        $this->get('/')->assertDontSee('Out tomorrow');
        $this->get(route('blog.show', $draft))->assertNotFound();
        $this->get(route('blog.show', $scheduled))->assertNotFound();
    }

    public function test_a_scheduled_article_comes_out_by_itself_when_its_time_arrives(): void
    {
        $post = BlogPost::factory()->scheduled()->create(['title' => 'Out tomorrow']);

        $this->travel(2)->days();

        $this->get('/blog')->assertSee('Out tomorrow');
        $this->get(route('blog.show', $post))->assertOk();
    }

    public function test_staff_who_are_signed_in_can_read_a_draft_and_are_told_it_is_not_out(): void
    {
        $draft = BlogPost::factory()->draft()->create();

        $this->actingAs(User::factory()->create())->get(route('blog.show', $draft))
            ->assertOk()
            ->assertSee('Not published yet');
    }

    public function test_an_article_shows_its_title_date_category_and_body(): void
    {
        $category = BlogCategory::factory()->create(['name' => 'Health knowledge']);
        $post = BlogPost::factory()->for($category, 'category')->create([
            'title' => 'Washing hands',
            'body' => '<h2>Seven steps</h2><p>With soap, for twenty seconds.</p>',
            'published_at' => '2026-10-01 09:00',
        ]);

        $this->get(route('blog.show', $post))
            ->assertOk()
            ->assertSee('<title>Washing hands · ', false)
            ->assertSee('Health knowledge')
            ->assertSee('1 October 2026')
            ->assertSee('Seven steps')
            ->assertSee('With soap, for twenty seconds.');
    }

    public function test_a_script_written_into_an_article_never_reaches_the_reader(): void
    {
        $post = BlogPost::factory()->create([
            'title' => 'A <script>alert("t")</script> title',
            'body' => '<p>Fine.</p><script>alert("b")</script><p onclick="alert(1)">Also fine.</p><a href="javascript:alert(2)">link</a>',
        ]);

        $page = $this->get(route('blog.show', $post))->assertOk()->getContent();

        $this->assertStringNotContainsString('alert("t")</script>', $page);
        $this->assertStringNotContainsString('<script>alert("b")', $page);
        $this->assertStringNotContainsString('onclick=', $page);
        $this->assertStringNotContainsString('javascript:alert', $page);
        $this->assertStringContainsString('Also fine.', $page);
    }

    public function test_a_thai_title_makes_a_thai_address_and_two_of_the_same_do_not_clash(): void
    {
        $first = BlogPost::factory()->create(['title' => 'การล้างมือ 7 ขั้นตอน']);
        $second = BlogPost::factory()->create(['title' => 'การล้างมือ 7 ขั้นตอน']);

        $this->assertSame('การล้างมือ-7-ขั้นตอน', $first->slug);
        $this->assertSame('การล้างมือ-7-ขั้นตอน-2', $second->slug);
        $this->get(route('blog.show', $first))->assertOk();

        // Saving again keeps the address it has.
        $first->update(['excerpt' => 'x']);
        $this->assertSame('การล้างมือ-7-ขั้นตอน', $first->fresh()->slug);
    }

    public function test_the_categories_filter_the_list_and_an_empty_one_is_not_offered(): void
    {
        $health = BlogCategory::factory()->create(['name' => 'Health knowledge']);
        $news = BlogCategory::factory()->create(['name' => 'Activities']);
        BlogCategory::factory()->create(['name' => 'Nothing in it']);
        BlogPost::factory()->for($health, 'category')->create(['title' => 'About dengue']);
        BlogPost::factory()->for($news, 'category')->create(['title' => 'Blood donation day']);

        $this->get('/blog?category='.$health->slug)
            ->assertOk()
            ->assertSee('About dengue')
            ->assertDontSee('Blood donation day')
            ->assertDontSee('Nothing in it');

        $this->get('/blog?category=no-such')->assertOk()->assertSee('About dengue')->assertSee('Blood donation day');
        $this->get('/blog?category[]=x')->assertOk();
    }

    public function test_the_list_is_in_pages_of_nine(): void
    {
        BlogPost::factory()->count(10)->create();

        $this->get('/blog')->assertSee('1 / 2')->assertSee('rel="next"', false);
        $this->get('/blog?page=2')->assertOk()->assertSee('rel="prev"', false);
    }

    public function test_the_home_page_shows_the_three_newest_articles(): void
    {
        $this->get('/')->assertDontSee('All articles');

        foreach ([4, 3, 2, 1] as $days) {
            BlogPost::factory()->create(['title' => "From $days days ago", 'published_at' => now()->subDays($days)]);
        }

        $this->get('/')
            ->assertSee('From 1 days ago')
            ->assertSee('From 3 days ago')
            ->assertDontSee('From 4 days ago')
            ->assertSee('All articles');
    }

    public function test_a_reader_in_english_gets_the_english_where_there_is_some(): void
    {
        $category = BlogCategory::factory()->create(['name' => 'ความรู้สุขภาพ', 'name_en' => 'Health knowledge']);
        $both = BlogPost::factory()->for($category, 'category')->create([
            'title' => 'ไข้เลือดออก', 'title_en' => 'Dengue fever',
            'body' => '<p>เนื้อหาไทย</p>', 'body_en' => '<p>English text</p>',
        ]);
        $thaiOnly = BlogPost::factory()->create(['title' => 'บริจาคโลหิต', 'body' => '<p>มีแต่ภาษาไทย</p>', 'body_en' => '<p></p>']);

        $this->withUnencryptedCookie(SetLocale::COOKIE, 'en')->get(route('blog.show', $both))
            ->assertSee('Dengue fever')->assertSee('English text')->assertSee('Health knowledge')->assertDontSee('เนื้อหาไทย');
        $this->withUnencryptedCookie(SetLocale::COOKIE, 'en')->get(route('blog.show', $thaiOnly))
            ->assertSee('บริจาคโลหิต')->assertSee('มีแต่ภาษาไทย');
        $this->withUnencryptedCookie(SetLocale::COOKIE, 'th')->get(route('blog.show', $both))
            ->assertSee('ไข้เลือดออก')->assertSee('เนื้อหาไทย')->assertDontSee('English text');
    }

    public function test_the_date_is_in_the_buddhist_year_for_a_reader_in_thai(): void
    {
        $post = BlogPost::factory()->create(['published_at' => '2026-10-01 09:00']);

        $this->withUnencryptedCookie(SetLocale::COOKIE, 'th')->get(route('blog.show', $post))->assertSee('1 ตุลาคม 2569');
    }

    public function test_an_author_writes_and_publishes_an_article_with_a_cover(): void
    {
        Storage::fake('public');
        $this->actingAs($author = User::factory()->create());
        $category = BlogCategory::factory()->create();

        Livewire::test(CreateBlogPost::class)
            ->fillForm([
                'title' => 'Washing hands',
                'excerpt' => 'Seven steps with soap.',
                'body' => '<p>With soap, for twenty seconds.</p>',
                'status' => BlogPost::STATUS_PUBLISHED,
                'blog_category_id' => $category->id,
                'cover_path' => UploadedFile::fake()->image('cover.jpg', 2400, 1350),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = BlogPost::firstOrFail();

        $this->assertTrue($post->author->is($author));
        $this->assertTrue($post->isPublished());
        $this->assertSame('washing-hands', $post->slug);
        Storage::disk('public')->assertExists($post->cover_path);
        $this->assertStringEndsWith('.webp', $post->cover_path);
        $this->get('/blog')->assertSee('Washing hands')->assertSee('Seven steps with soap.');
    }

    public function test_an_article_needs_a_title_and_something_written(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateBlogPost::class)
            ->fillForm(['title' => '', 'body' => ''])
            ->call('create')
            ->assertHasFormErrors(['title', 'body']);

        $this->assertSame(0, BlogPost::count());
    }

    public function test_an_article_is_a_draft_until_someone_publishes_it_and_can_be_scheduled(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateBlogPost::class)
            ->fillForm(['title' => 'Later', 'body' => '<p>Text.</p>'])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = BlogPost::firstOrFail();
        $this->assertSame('Draft', $post->statusLabel());

        Livewire::test(EditBlogPost::class, ['record' => $post->slug])
            ->fillForm(['status' => BlogPost::STATUS_PUBLISHED, 'published_at' => now()->addWeek()->format('Y-m-d H:i:s')])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Scheduled', $post->fresh()->statusLabel());
        auth()->logout();
        $this->get('/blog')->assertDontSee('Later');
    }

    public function test_replacing_or_deleting_an_article_removes_its_old_cover(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('blog/covers/old.webp', 'x');
        Storage::disk('public')->put('blog/covers/new.webp', 'y');
        $post = BlogPost::factory()->create(['cover_path' => 'blog/covers/old.webp']);

        $post->update(['cover_path' => 'blog/covers/new.webp']);
        Storage::disk('public')->assertMissing('blog/covers/old.webp');

        $post->delete();
        Storage::disk('public')->assertMissing('blog/covers/new.webp');
    }

    public function test_staff_manage_categories_and_deleting_one_keeps_its_articles(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ManageBlogCategories::class)
            ->callAction('create', ['name' => 'ความรู้สุขภาพ', 'name_en' => 'Health knowledge', 'sort_order' => 1])
            ->assertHasNoFormErrors();

        $category = BlogCategory::firstOrFail();
        $this->assertSame('ความรู้สุขภาพ', $category->slug);

        $post = BlogPost::factory()->for($category, 'category')->create();
        $category->delete();

        $this->assertNull($post->fresh()->blog_category_id);
        $this->get(route('blog.show', $post))->assertOk();
    }

    public function test_the_blog_screens_open_for_an_author_and_read_in_thai(): void
    {
        app()->setLocale('th');
        $this->actingAs(User::factory()->create());

        $this->get('/admin/blog-posts')->assertOk()->assertSee('บทความ');
        $this->get('/admin/blog-posts/create')->assertOk()->assertSee('ฉบับภาษาอังกฤษ');
        $this->get('/admin/blog-categories')->assertOk()->assertSee('หมวดหมู่');
    }
}
