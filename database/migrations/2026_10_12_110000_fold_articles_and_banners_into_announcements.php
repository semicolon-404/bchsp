<?php

use App\Support\Slug;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One place for staff to post: an announcement is now also what an article and a poster were.
 *
 * - announcements.gallery: the photographs of an activity, shown as an album under it (was blog_posts.gallery).
 * - announcements.is_popup: its picture opens over the page when the site is first opened (was banners.is_popup).
 * - Two more types every installation starts with: health knowledge and activities.
 * - Every article and every banner is copied into announcements. The old tables are left as they are and
 *   no longer read: nothing is lost if this has to be looked at again.
 * - Pages opened under "articles" are counted with the news from now on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->json('gallery')->nullable()->after('in_slider');
            $table->boolean('is_popup')->default(false)->after('in_slider');
        });

        $now = now();
        $news = DB::table('announcement_types')->where('slug', 'news')->value('id');

        $knowledge = $this->type('knowledge', 'ความรู้สุขภาพ', 'Health knowledge', 'heart', 'green', 4);
        $activities = $this->type('activities', 'ข่าวกิจกรรม', 'Activities', 'calendar-days', 'pink', 5);

        // A category of articles becomes the type of the same name, made when there is none.
        $types = [];

        foreach (DB::table('blog_categories')->get() as $category) {
            $types[$category->id] = DB::table('announcement_types')->where('name', $category->name)->value('id')
                ?? $this->type(Slug::make($category->name, 'type'), $category->name, $category->name_en, 'document-text', 'accent', 6 + $category->sort_order);
        }

        foreach (DB::table('blog_posts')->orderBy('id')->get() as $post) {
            DB::table('announcements')->insert([
                'announcement_type_id' => $types[$post->blog_category_id] ?? $knowledge,
                'author_id' => $post->author_id,
                'department_id' => $post->department_id,
                'title' => $post->title,
                'title_en' => $post->title_en,
                'slug' => $this->slug($post->slug),
                'body' => $post->body,
                'body_en' => $post->body_en,
                'image_path' => $post->cover_path,
                'gallery' => $post->gallery,
                'views' => $post->views,
                // A draft had no date: it waits, scheduled far ahead, until staff give it one.
                'starts_at' => $post->status === 'published' ? ($post->published_at ?? $post->created_at ?? $now) : $now->copy()->addYears(10),
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
            ]);
        }

        foreach (DB::table('banners')->orderBy('position')->orderBy('id')->get() as $banner) {
            DB::table('announcements')->insert([
                'announcement_type_id' => $news,
                'title' => $banner->title,
                'slug' => $this->slug(Slug::make($banner->title, 'announcement')),
                'image_path' => $banner->image_path,
                'in_slider' => $banner->placement === 'banner',
                'is_popup' => $banner->is_popup,
                // Where the picture led, kept as a link under it.
                'links' => $banner->url && str_starts_with($banner->url, 'https://') ? json_encode([['label' => '', 'url' => $banner->url]]) : null,
                'starts_at' => $banner->starts_at ?? $banner->created_at ?? $now,
                // One that was switched off is filed as ended.
                'ends_at' => $banner->is_active ? $banner->ends_at : ($banner->ends_at ?? $now),
                'created_at' => $banner->created_at,
                'updated_at' => $banner->updated_at,
            ]);
        }

        foreach (DB::table('site_visits')->where('section', 'articles')->get() as $row) {
            if (DB::table('site_visits')->where(['day' => $row->day, 'section' => 'news'])->increment('views', $row->views) === 0) {
                DB::table('site_visits')->insert(['day' => $row->day, 'section' => 'news', 'views' => $row->views]);
            }
        }

        DB::table('site_visits')->where('section', 'articles')->delete();
    }

    /** What was copied stays among the announcements; only the columns go. */
    public function down(): void
    {
        Schema::table('announcements', fn (Blueprint $table) => $table->dropColumn(['gallery', 'is_popup']));
    }

    /** The id of the type with this address, made when there is none. */
    private function type(string $slug, string $name, ?string $nameEn, string $icon, string $tone, int $order): int
    {
        return DB::table('announcement_types')->where('slug', $slug)->value('id')
            ?? DB::table('announcement_types')->insertGetId([
                'name' => $name, 'name_en' => $nameEn, 'slug' => $slug, 'icon' => $icon, 'tone' => $tone, 'sort_order' => $order,
                'created_at' => now(), 'updated_at' => now(),
            ]);
    }

    /** The address, with "-2", "-3"... added until no announcement has it. */
    private function slug(string $base): string
    {
        $slug = $base;
        $suffix = 2;

        while (DB::table('announcements')->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
};
