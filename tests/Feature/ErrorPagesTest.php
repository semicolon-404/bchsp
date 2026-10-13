<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** When something goes wrong the visitor is told so in plain words, on a page of the site's own, with the ways out. */
class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_address_nothing_answers_to_says_so_and_offers_the_ways_out(): void
    {
        SiteSettings::save(['contact.phone' => '044-579076', 'contact.emergency_phone' => '1669']);

        $page = $this->get('/no-such-page')
            ->assertNotFound()
            ->assertSeeInOrder(['404', 'This page is not here', 'href="'.url('/').'"', 'href="'.url('/announcements').'"', 'href="'.url('/search').'"', 'href="tel:044579076"', 'href="tel:1669"'], false)
            ->assertSee('<meta name="robots" content="noindex">', false)
            ->getContent();

        // It stands on its own: nothing to fetch, so it shows whatever it was that failed.
        $this->assertStringNotContainsString('/build/assets/', $page);
        $this->assertStringNotContainsString('<link rel="stylesheet"', $page);
        // And nothing of the framework's own page.
        $this->assertStringNotContainsString('Not Found', $page);
    }

    public function test_a_page_that_is_not_the_visitors_to_open_says_so(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/users')
            ->assertForbidden()
            ->assertSee('This page is not for you to open');
    }

    public function test_the_error_pages_read_in_thai_and_every_one_of_them_is_drawn(): void
    {
        app()->setLocale('th');

        $this->get('/no-such-page')->assertSee('ไม่พบหน้านี้')->assertSee('หน้าแรก');

        foreach ([403, 404, 419, 429, 500, 503] as $code) {
            $html = view("errors.$code")->render();

            $this->assertStringContainsString((string) $code, $html);
            $this->assertStringContainsString('<h1>', $html);
        }

        // A fault of the site's own does not send the visitor round pages that may be failing too.
        $this->assertStringNotContainsString('class="ways"', view('errors.500')->render());
    }

    public function test_the_tab_of_the_browser_carries_the_sites_own_picture(): void
    {
        $this->get('/')->assertSee('<link rel="icon" type="image/png" sizes="192x192" href="'.route('icon', ['size' => 192]).'">', false);
    }
}
