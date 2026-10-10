<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteSettings as SettingsPage;
use App\Http\Middleware\SetLocale;
use App\Models\User;
use App\Support\Footer;
use App\Support\Menus;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FooterAndOwnLinksTest extends TestCase
{
    use RefreshDatabase;

    /** The footer as it is in a page. */
    private function footer(string $address = '/'): string
    {
        preg_match('/<footer.*?<\/footer>/s', $this->get($address)->assertOk()->getContent(), $found);

        return $found[0];
    }

    public function test_until_anything_is_set_the_footer_is_what_it_always_was(): void
    {
        SiteSettings::save(['app.name' => 'Buachet Hospital']);

        $footer = $this->footer();

        $this->assertStringContainsString('© '.now()->year.' Buachet Hospital', $footer);
        $this->assertStringContainsString('Pages opened:', $footer);
        $this->assertSame([], Footer::groups());
        $this->assertNull(Footer::about());
    }

    public function test_an_administrator_writes_the_footers_words_and_groups_its_links(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SettingsPage::class)
            ->fillForm([
                'footer__about' => 'A 30-bed community hospital in Surin.',
                'footer__copyright' => 'Buachet Hospital, Ministry of Public Health',
                'footer__show_visits' => false,
                'footer_links' => [
                    ['group' => 'Related agencies', 'label' => 'Surin Provincial Health Office', 'url' => 'https://surin.moph.go.th'],
                    ['group' => 'Related agencies', 'label' => 'Ministry of Public Health', 'url' => 'https://www.moph.go.th'],
                    ['group' => '', 'label' => 'Site map', 'url' => 'http://localhost/menu'],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        auth()->logout();
        $footer = $this->footer();

        $this->assertStringContainsString('A 30-bed community hospital in Surin.', $footer);
        $this->assertStringContainsString('Buachet Hospital, Ministry of Public Health', $footer);
        $this->assertStringNotContainsString('Pages opened:', $footer);
        $this->assertMatchesRegularExpression('/Related agencies.*Surin Provincial Health Office.*Ministry of Public Health.*Links.*Site map/s', $footer);
        // A link that leaves the site opens in a new tab; one to this site does not.
        $this->assertMatchesRegularExpression('/href="https:\/\/surin\.moph\.go\.th"\s+target="_blank" rel="noopener noreferrer"/', $footer);
        $this->assertDoesNotMatchRegularExpression('/href="http:\/\/localhost\/menu"\s+target="_blank"/', $footer);
        // On every page, not only the first.
        $this->assertStringContainsString('Related agencies', $this->footer('/contact'));
    }

    public function test_only_links_with_a_name_and_a_web_address_are_kept(): void
    {
        SiteSettings::save(['footer.links' => [
            ['group' => 'A', 'label' => 'Good', 'url' => 'https://example.com'],
            ['group' => 'A', 'label' => 'Script', 'url' => 'javascript:alert(1)'],
            ['group' => 'A', 'label' => '', 'url' => 'https://example.com/nameless'],
            ['group' => '<script>alert(2)</script>', 'label' => '<b>Bold</b>', 'url' => 'https://example.com/b'],
            'nonsense',
        ]]);

        $this->assertCount(2, Footer::links());

        $footer = $this->footer();

        $this->assertStringNotContainsString('javascript:alert', $footer);
        $this->assertStringNotContainsString('<script>alert(2)', $footer);
        $this->assertStringNotContainsString('<b>Bold</b>', $footer);
        $this->assertStringNotContainsString('nameless', $footer);
    }

    public function test_the_settings_page_refuses_a_link_that_is_not_a_web_address(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SettingsPage::class)
            ->fillForm(['footer_links' => [['group' => '', 'label' => 'Bad', 'url' => 'javascript:alert(1)']]])
            ->call('save')
            ->assertHasFormErrors();

        Livewire::test(SettingsPage::class)
            ->fillForm(['menu_links' => [['label' => 'Bad', 'url' => 'ftp://example.com', 'place' => 'top']]])
            ->call('save')
            ->assertHasFormErrors();
    }

    public function test_the_footers_words_follow_the_readers_language(): void
    {
        SiteSettings::save(['footer.about' => 'โรงพยาบาลชุมชนขนาด 30 เตียง', 'footer.about_en' => 'A 30-bed community hospital']);

        $this->withUnencryptedCookie(SetLocale::COOKIE, 'en');
        $this->assertStringContainsString('A 30-bed community hospital', $this->footer());

        $this->withUnencryptedCookie(SetLocale::COOKIE, 'th');
        $footer = $this->footer();
        $this->assertStringContainsString('โรงพยาบาลชุมชนขนาด 30 เตียง', $footer);
        $this->assertStringContainsString('© '.(now()->year + 543), $footer);
    }

    public function test_an_administrator_adds_links_of_their_own_to_the_menus(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SettingsPage::class)
            ->fillForm(['menu_links' => [
                ['label' => 'จองคิวออนไลน์', 'label_en' => 'Book a visit', 'url' => 'https://queue.example.com', 'place' => 'top'],
                ['label' => 'Annual report', 'label_en' => '', 'url' => 'https://example.com/annual', 'place' => 'about'],
                ['label' => 'Press releases', 'label_en' => '', 'url' => 'https://example.com/press', 'place' => 'news'],
            ]])
            ->call('save')
            ->assertHasNoFormErrors();

        auth()->logout();

        $top = Menus::items('top');
        $this->assertSame(['home', 'about', 'ita', 'news', 'procurement', 'own-0'], array_column($top, 'key'));
        $this->assertSame('https://queue.example.com', end($top)['url']);
        $this->assertContains('Press releases', array_column(collect($top)->firstWhere('key', 'news')['children'], 'label'));

        // In the bar, opening in a new tab; on the About page; on the page that lists everything. Not in the phone's dock.
        $page = $this->get('/')->getContent();
        $this->assertMatchesRegularExpression('/href="https:\/\/queue\.example\.com"[^>]*target="_blank" rel="noopener noreferrer"[^>]*>Book a visit/s', $page);
        $this->get('/about')->assertSee('Annual report')->assertSee('https://example.com/annual', false);
        $this->get('/menu')->assertSee('Book a visit')->assertSee('Press releases');
        $this->assertNotContains('own-0', array_column(Menus::items('tabs'), 'key'));

        // The tests read the site in English; a reader in Thai gets the name as it was first written.
        $this->withUnencryptedCookie(SetLocale::COOKIE, 'th')->get('/')->assertSee('จองคิวออนไลน์')->assertDontSee('Book a visit');
    }

    public function test_articles_and_downloads_can_be_left_out_of_the_news_menu(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SettingsPage::class)
            ->assertFormSet(['menu_news_shown' => ['articles', 'downloads']])
            ->fillForm(['menu_news_shown' => ['articles']])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['downloads'], Menus::newsHidden());

        $labels = array_column(collect(Menus::items('top'))->firstWhere('key', 'news')['children'], 'label');

        $this->assertContains('Health articles', $labels);
        $this->assertNotContains('Downloads', $labels);
        // The page is still there for anyone with its address.
        $this->get('/downloads')->assertOk();
    }

    public function test_a_made_up_place_or_address_never_reaches_the_menu(): void
    {
        SiteSettings::save(['menu.links' => [
            ['label' => 'Bad place', 'url' => 'https://example.com', 'place' => 'admin'],
            ['label' => 'Bad address', 'url' => 'javascript:alert(1)', 'place' => 'top'],
            ['label' => 'Fine', 'url' => 'https://example.com/fine', 'place' => 'top'],
        ], 'menu.news_hidden' => ['articles', 'no-such']]);

        $this->assertSame(['Fine'], array_column(Menus::ownLinks('top'), 'label'));
        $this->assertSame(['articles'], Menus::newsHidden());
        $this->get('/')->assertOk()->assertDontSee('javascript:alert', false);
    }

    public function test_the_sections_read_in_thai(): void
    {
        app()->setLocale('th');

        $this->actingAs(User::factory()->admin()->create())->get('/admin/site-settings')
            ->assertSee('ส่วนท้ายของเว็บ (Footer)')
            ->assertSee('ลิงก์ที่กำหนดเอง');
    }
}
