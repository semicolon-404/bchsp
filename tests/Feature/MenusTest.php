<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteSettings as SettingsPage;
use App\Filament\Resources\StaffLinks\Pages\ManageStaffLinks;
use App\Models\Announcement;
use App\Models\AnnouncementType;
use App\Models\BlogPost;
use App\Models\Document;
use App\Models\SitePage;
use App\Models\StaffLink;
use App\Models\User;
use App\Support\Menus;
use App\Support\SiteSettings;
use Database\Seeders\StaffLinkSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MenusTest extends TestCase
{
    use RefreshDatabase;

    /** The top bar as it is in a page: from its opening tag to its closing one. */
    private function topBar(string $page): string
    {
        preg_match('/<nav class="hidden items-stretch.*?<\/nav>/s', $page, $found);

        return $found[0] ?? '';
    }

    /** The groups of the top bar that are marked as the one the visitor is in, after opening a page. */
    private function markedOn(string $url): array
    {
        $this->get($url)->assertOk();

        return array_column(array_filter(Menus::items('top'), fn (array $item) => $item['active']), 'key');
    }

    public function test_the_public_menu_is_the_five_groups_a_government_hospital_shows(): void
    {
        $this->assertSame(['home', 'about', 'ita', 'news', 'procurement'], Menus::keys('top'));
        $this->assertSame(['home', 'news', 'procurement', 'ita', 'menu'], Menus::keys('tabs'));

        $this->assertSame(
            ['Home', 'About us', 'ITA', 'News', 'Procurement'],
            array_column(Menus::items('top'), 'label'),
        );
    }

    public function test_about_us_opens_onto_the_pages_filed_there_then_departments_and_contact(): void
    {
        SitePage::factory()->create(['title' => 'Vision and mission', 'section' => 'about', 'position' => 2]);
        SitePage::factory()->create(['title' => 'History of the hospital', 'section' => 'about', 'position' => 1]);
        SitePage::factory()->create(['title' => 'Filed nowhere', 'show_in_footer' => false]);
        SitePage::factory()->create(['title' => 'Not out yet', 'section' => 'about', 'is_published' => false]);

        $bar = $this->topBar($this->get('/')->assertOk()->getContent());

        $this->assertMatchesRegularExpression('/About us.*History of the hospital.*Vision and mission.*Departments.*Contact us.*ITA/s', $bar);
        $this->assertStringNotContainsString('Filed nowhere', $bar);
        $this->assertStringNotContainsString('Not out yet', $bar);
    }

    public function test_the_menu_follows_a_page_being_filed_renamed_or_removed_at_once(): void
    {
        $page = SitePage::factory()->create(['title' => 'Old name', 'section' => 'about']);
        $this->assertStringContainsString('Old name', $this->topBar($this->get('/')->getContent()));

        $page->update(['title' => 'New name']);
        $this->assertStringContainsString('New name', $this->topBar($this->get('/')->getContent()));

        $page->update(['section' => null]);
        $this->assertStringNotContainsString('New name', $this->topBar($this->get('/')->getContent()));
    }

    public function test_news_opens_onto_every_kind_of_announcement_but_procurement_then_articles_and_downloads(): void
    {
        AnnouncementType::factory()->create(['name' => 'Public hearings', 'sort_order' => 9]);

        $news = collect(Menus::items('top'))->firstWhere('key', 'news');

        $this->assertSame(['News', 'Job openings', 'Public hearings', 'Health articles', 'Downloads'], array_column($news['children'], 'label'));
        $this->assertSame(route('announcements.index', ['type' => 'jobs']), $news['children'][1]['url']);

        $procurement = collect(Menus::items('top'))->firstWhere('key', 'procurement');

        $this->assertSame(route('announcements.index', ['type' => 'procurement']), $procurement['url']);
        $this->assertSame([], $procurement['children']);
    }

    public function test_procurement_is_left_out_when_that_kind_of_announcement_is_gone(): void
    {
        AnnouncementType::where('slug', 'procurement')->delete();

        $this->assertNotContains('procurement', array_column(Menus::items('top'), 'key'));
        $this->get('/')->assertOk();
    }

    public function test_the_group_someone_is_in_is_marked(): void
    {
        $post = BlogPost::factory()->create();
        $notice = Announcement::factory()->create();
        $page = SitePage::factory()->create(['section' => 'about']);

        $this->assertSame(['home'], $this->markedOn('/'));
        $this->assertSame(['news'], $this->markedOn(route('blog.show', $post)));
        $this->assertSame(['news'], $this->markedOn(route('announcements.show', $notice)));
        $this->assertSame(['news'], $this->markedOn('/announcements?type=jobs'));
        $this->assertSame(['procurement'], $this->markedOn('/announcements?type=procurement'));
        $this->assertSame(['news'], $this->markedOn('/downloads'));
        $this->assertSame(['about'], $this->markedOn(route('pages.show', $page)));
        $this->assertSame(['about'], $this->markedOn('/departments'));
        $this->assertSame(['about'], $this->markedOn('/contact'));
        $this->assertSame(['ita'], $this->markedOn('/ita'));
        $this->assertSame([], $this->markedOn('/staff'));
    }

    public function test_the_dock_leads_to_each_group_and_to_the_page_that_lists_everything(): void
    {
        $page = $this->get('/')->getContent();

        preg_match('/<nav aria-label="Main" class="dock lg:hidden">.*?<\/nav>/s', $page, $dock);

        $this->assertSame(5, substr_count($dock[0], '<li>'));
        $this->assertStringContainsString('href="'.route('menu').'"', $dock[0]);
        $this->assertStringContainsString('href="'.e(route('announcements.index', ['type' => 'procurement'])).'"', $dock[0]);
    }

    public function test_the_menu_page_lists_every_group_and_what_it_opens_onto(): void
    {
        SitePage::factory()->create(['title' => 'History of the hospital', 'section' => 'about']);

        $this->get('/menu')
            ->assertOk()
            ->assertSeeInOrder(['Everything on this website', 'About us', 'History of the hospital', 'Departments', 'Contact us', 'ITA', 'News', 'Job openings', 'Health articles', 'Downloads', 'Procurement', 'For staff']);
    }

    public function test_what_staff_use_has_a_page_of_its_own_out_of_the_public_menu_and_of_search_engines(): void
    {
        StaffLink::factory()->create(['title' => 'Repair requests', 'url' => 'https://example.com/repair', 'group' => 'Internal systems', 'note' => 'Inside the hospital only']);
        StaffLink::factory()->create(['title' => 'Switched off', 'is_active' => false]);
        StaffLink::factory()->create(['title' => 'Not a web address', 'url' => 'javascript:alert(1)']);

        $this->get('/staff')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex">', false)
            ->assertSee('Internal systems')
            ->assertSee('Repair requests')
            ->assertSee('https://example.com/repair', false)
            ->assertSee('Inside the hospital only')
            ->assertSee('href="'.url('/admin').'"', false)
            ->assertDontSee('Switched off')
            ->assertDontSee('javascript:alert', false);

        $this->assertStringNotContainsString('Repair requests', $this->topBar($this->get('/')->getContent()));
        $this->get('/')->assertDontSee('<meta name="robots" content="noindex">', false);
    }

    public function test_the_starting_list_of_staff_services_is_added_once(): void
    {
        $this->seed(StaffLinkSeeder::class);
        $count = StaffLink::count();

        $this->assertGreaterThan(10, $count);

        StaffLink::query()->limit(3)->delete();
        $this->seed(StaffLinkSeeder::class);

        $this->assertSame($count - 3, StaffLink::count());
    }

    public function test_only_an_administrator_arranges_the_staff_services(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/staff-links')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageStaffLinks::class)
            ->callAction('create', ['title' => 'Bad', 'url' => 'javascript:alert(1)'])
            ->assertHasFormErrors(['url']);

        Livewire::test(ManageStaffLinks::class)
            ->callAction('create', ['title' => 'P4P', 'url' => 'http://10.55.107.3/p4p', 'group' => 'Internal systems'])
            ->assertHasNoFormErrors();

        $this->assertSame(1, StaffLink::count());
    }

    public function test_ita_documents_are_filed_by_fiscal_year_newest_first_and_kept_off_the_downloads_page(): void
    {
        Storage::fake('public');
        Document::factory()->create(['title' => 'Procurement plan 2568', 'section' => 'ita', 'year' => 2568, 'group' => 'MOIT 5']);
        Document::factory()->create(['title' => 'Ethics standard', 'section' => 'ita', 'year' => 2569, 'group' => 'MOIT 10']);
        Document::factory()->create(['title' => 'Structure of the hospital', 'section' => 'ita', 'year' => 2569, 'group' => 'MOIT 2']);
        Document::factory()->create(['title' => 'Leave form']);

        $this->get('/ita')
            ->assertOk()
            ->assertSeeInOrder(['Fiscal year 2569', 'MOIT 2', 'Structure of the hospital', 'MOIT 10', 'Ethics standard', 'Fiscal year 2568', 'MOIT 5', 'Procurement plan 2568'])
            ->assertDontSee('Leave form');

        $this->get('/downloads')->assertSee('Leave form')->assertDontSee('Ethics standard');
    }

    public function test_an_administrator_reorders_the_top_bar_and_the_dock(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SettingsPage::class)
            // A list in a form is kept under made-up keys; what matters is its rows in order.
            ->assertFormSet(fn (array $state) => array_column(array_values($state['menu_top']), 'key') === ['home', 'about', 'ita', 'news', 'procurement'])
            ->fillForm(['menu_top' => [['key' => 'news'], ['key' => 'home'], ['key' => 'contact']], 'menu_tabs' => [['key' => 'home']]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['news', 'home', 'contact'], Menus::keys('top'));

        auth()->logout();
        $page = $this->get('/')->getContent();

        $this->assertMatchesRegularExpression('/News.*Home.*Contact us/s', $this->topBar($page));
        // A dock with one tab is no dock.
        $this->assertStringNotContainsString('class="dock lg:hidden"', $page);
    }

    public function test_an_untouched_menu_is_not_saved_so_new_places_reach_it_later(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SettingsPage::class)->call('save')->assertHasNoFormErrors();

        $this->assertNull(SiteSettings::get('menu.top'));
        $this->assertSame('null', DB::table('settings')->where('key', 'menu.top')->value('value'));
    }

    public function test_only_known_places_are_kept_each_once_and_no_more_than_fit(): void
    {
        $this->assertSame(['blog'], Menus::toStore('top', ['blog', 'blog', 'no-such', ['key' => 'blog'], null, 7]));
        $this->assertNull(Menus::toStore('top', ['home', 'about', 'ita', 'news', 'procurement']));
        $this->assertNull(Menus::toStore('tabs', []));

        SiteSettings::save(['menu.top' => ['<script>', 'blog']]);
        $this->assertSame(['blog'], Menus::keys('top'));
        $this->get('/')->assertOk();
    }

    public function test_the_same_place_cannot_be_picked_twice(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SettingsPage::class)
            ->fillForm(['menu_top' => [['key' => 'blog'], ['key' => 'blog']]])
            ->call('save')
            ->assertHasFormErrors();
    }

    public function test_the_menus_read_in_thai(): void
    {
        app()->setLocale('th');

        $bar = $this->topBar($this->get('/')->assertSee('aria-label="เมนูหลัก"', false)->getContent());

        foreach (['หน้าแรก', 'เกี่ยวกับเรา', 'ITA', 'ข่าวประชาสัมพันธ์', 'รับสมัครงาน', 'บทความความรู้สุขภาพ', 'จัดซื้อจัดจ้าง'] as $label) {
            $this->assertStringContainsString($label, $bar);
        }

        $this->get('/staff')->assertSee('บริการสำหรับเจ้าหน้าที่');
    }
}
