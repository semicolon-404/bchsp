<?php

namespace Tests\Feature;

use App\Models\Announcement;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/** A post put up today says how long ago; an older one its day; both the hour. */
class PostedTimeTest extends TestCase
{
    public function test_today_reads_as_how_long_ago_and_older_as_the_day(): void
    {
        app()->setLocale('th');
        Carbon::setTestNow('2026-11-05 15:00:00');
        $post = new Announcement;

        $post->starts_at = Carbon::parse('2026-11-05 12:00:00');
        $this->assertSame('3 ชั่วโมงที่แล้ว · 12:00 น.', $post->shownAt());

        $post->starts_at = Carbon::parse('2026-11-05 14:59:40');
        $this->assertSame('เมื่อสักครู่ · 14:59 น.', $post->shownAt());

        $post->starts_at = Carbon::parse('2026-11-03 09:30:00');
        $this->assertSame('3 พ.ย. 2569 · 09:30 น.', $post->shownAt());

        Carbon::setTestNow();
    }
}
