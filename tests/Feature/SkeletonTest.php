<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkeletonTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_opens(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_the_admin_panel_asks_a_visitor_to_sign_in(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk();
    }

    public function test_times_are_kept_on_the_clock_of_thailand(): void
    {
        $this->assertSame('Asia/Bangkok', config('app.timezone'));
        $this->assertSame('Asia/Bangkok', now()->timezoneName);
    }
}
