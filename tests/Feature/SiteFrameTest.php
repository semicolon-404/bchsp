<?php

namespace Tests\Feature;

use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteFrameTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_names_the_hospital(): void
    {
        SiteSettings::save(['app.name' => 'Buachet Hospital']);

        $this->get('/')
            ->assertOk()
            ->assertSee('<title>Buachet Hospital</title>', false)
            ->assertSee('<h1', false)
            ->assertSee('Buachet Hospital');
    }

    public function test_every_page_can_be_skipped_into_and_fits_a_phone(): void
    {
        $this->get('/')
            ->assertSee('name="viewport" content="width=device-width, initial-scale=1"', false)
            ->assertSee('href="#main"', false)
            ->assertSee('id="main"', false)
            // Zooming is never switched off.
            ->assertDontSee('user-scalable')
            ->assertDontSee('maximum-scale');
    }

    public function test_the_phone_numbers_can_be_dialled_from_the_page(): void
    {
        SiteSettings::save(['contact.phone' => '044 579 076', 'contact.emergency_phone' => '1669']);

        $this->get('/')
            ->assertSee('tel:044579076', false)
            ->assertSee('tel:1669', false)
            ->assertSee('Call 044 579 076')
            ->assertSee('Emergency');
    }

    public function test_what_has_not_been_filled_in_is_left_out(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('tel:', false)
            ->assertDontSee('mailto:', false)
            ->assertDontSee('Follow us');
    }

    public function test_the_footer_carries_the_address_and_the_social_links(): void
    {
        SiteSettings::save([
            'contact.address' => '1 Moo 1, Buachet, Surin 32230',
            'contact.email' => 'info@example.com',
            'social.facebook' => 'https://www.facebook.com/buachet',
        ]);

        $this->get('/')
            ->assertSee('1 Moo 1, Buachet, Surin 32230')
            ->assertSee('mailto:info@example.com', false)
            ->assertSee('https://www.facebook.com/buachet', false)
            ->assertSee('rel="noopener noreferrer"', false);
    }

    public function test_the_year_is_the_buddhist_one(): void
    {
        $this->get('/')->assertSee((string) (now()->year + 543));
    }

    public function test_the_page_reads_in_thai(): void
    {
        app()->setLocale('th');
        SiteSettings::save(['contact.phone' => '044 579 076']);

        $this->get('/')
            ->assertSee('lang="th"', false)
            ->assertSee('ข้ามไปยังเนื้อหา')
            ->assertSee('ติดต่อเรา')
            ->assertSee('โทร 044 579 076');
    }

    public function test_no_colour_is_written_into_a_page_past_the_shared_ones(): void
    {
        foreach (['components/layouts/site.blade.php', 'home.blade.php'] as $view) {
            $this->assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{6}\b|\b(?:bg|text|border)-(?:red|teal|gray|zinc|slate|green|blue)-\d{2,3}\b/', file_get_contents(resource_path('views/'.$view)), $view);
        }
    }
}
