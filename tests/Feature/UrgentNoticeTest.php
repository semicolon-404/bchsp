<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteSettings as SettingsPage;
use App\Http\Middleware\SetLocale;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UrgentNoticeTest extends TestCase
{
    use RefreshDatabase;

    public function test_there_is_no_notice_until_one_is_switched_on(): void
    {
        SiteSettings::save(['alert.text' => 'Written but not switched on']);

        $this->get('/')->assertOk()->assertDontSee('Written but not switched on');
    }

    public function test_a_notice_is_at_the_top_of_every_page_above_the_header(): void
    {
        SiteSettings::save(['alert.enabled' => true, 'alert.text' => 'The dental clinic is closed this Friday.']);

        foreach (['/', '/announcements', '/contact', '/ita', '/about'] as $address) {
            $page = $this->get($address)->assertOk()->assertSee('The dental clinic is closed this Friday.')->getContent();

            $this->assertLessThan(strpos($page, '<header'), strpos($page, 'The dental clinic is closed'), $address);
        }
    }

    public function test_switched_on_with_nothing_written_it_takes_no_room(): void
    {
        SiteSettings::save(['alert.enabled' => true, 'alert.text' => '']);

        $this->get('/')->assertDontSee('role="status"', false);
    }

    public function test_it_can_link_to_the_details_but_only_to_a_web_address(): void
    {
        SiteSettings::save(['alert.enabled' => true, 'alert.text' => 'Power cut today.', 'alert.url' => 'https://example.com/notice']);
        $this->get('/')->assertSee('href="https://example.com/notice"', false)->assertSee('Read more');

        SiteSettings::save(['alert.url' => 'javascript:alert(1)']);
        $this->get('/')->assertSee('Power cut today.')->assertDontSee('javascript:alert', false)->assertDontSee('Read more');
    }

    public function test_what_is_written_is_printed_as_text(): void
    {
        SiteSettings::save(['alert.enabled' => true, 'alert.text' => '<script>alert(1)</script>']);

        $this->get('/')->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_a_reader_in_english_gets_the_english_where_there_is_some(): void
    {
        SiteSettings::save(['alert.enabled' => true, 'alert.text' => 'งดคลินิกทันตกรรม', 'alert.text_en' => 'Dental clinic closed']);

        $this->withUnencryptedCookie(SetLocale::COOKIE, 'en')->get('/')->assertSee('Dental clinic closed')->assertDontSee('งดคลินิกทันตกรรม');
        $this->withUnencryptedCookie(SetLocale::COOKIE, 'th')->get('/')->assertSee('งดคลินิกทันตกรรม');

        SiteSettings::save(['alert.text_en' => null]);
        $this->withUnencryptedCookie(SetLocale::COOKIE, 'en')->get('/')->assertSee('งดคลินิกทันตกรรม');
    }

    public function test_an_administrator_puts_a_notice_up_and_takes_it_down(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        // Switched on, it needs something to say.
        Livewire::test(SettingsPage::class)->fillForm(['alert__enabled' => true, 'alert__text' => ''])->call('save')->assertHasFormErrors(['alert__text']);

        Livewire::test(SettingsPage::class)
            ->fillForm(['alert__enabled' => true, 'alert__text' => 'No outpatient clinic on Monday.'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->get('/')->assertSee('No outpatient clinic on Monday.');

        Livewire::test(SettingsPage::class)->fillForm(['alert__enabled' => false])->call('save')->assertHasNoFormErrors();
        $this->get('/')->assertDontSee('No outpatient clinic on Monday.');
    }

    public function test_the_section_reads_in_thai(): void
    {
        app()->setLocale('th');

        $this->actingAs(User::factory()->admin()->create())->get('/admin/site-settings')->assertSee('ประกาศด่วน');
    }
}
