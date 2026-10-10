<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteSettings as SettingsPage;
use App\Models\BlogPost;
use App\Models\User;
use App\Support\Menus;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class MenusTest extends TestCase
{
    use RefreshDatabase;

    public function test_until_someone_arranges_them_the_menus_are_what_they_always_were(): void
    {
        $this->assertSame(['home', 'announcements', 'blog', 'departments'], Menus::keys('top'));
        $this->assertSame(['home', 'announcements', 'blog', 'departments'], Menus::keys('tabs'));

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['aria-label="Main"', route('home'), 'Home', route('announcements.index'), 'Announcements', route('blog.index'), 'Articles'], false)
            ->assertSee('class="dock lg:hidden"', false);
    }

    public function test_the_page_someone_is_on_is_marked_in_both_menus(): void
    {
        $post = BlogPost::factory()->create();

        $this->assertSame(2, substr_count($this->get('/')->getContent(), 'aria-current="page"'));
        // An article lights up "Articles", in the bar and in the dock.
        $page = $this->get(route('blog.show', $post))->getContent();
        $this->assertSame(2, substr_count($page, 'href="'.route('blog.index').'" aria-current="page"'));
    }

    public function test_an_administrator_reorders_the_top_bar_and_takes_a_tab_out_of_the_dock(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SettingsPage::class)
            // A list in a form is kept under made-up keys; what matters is its rows in order.
            ->assertFormSet(fn (array $state) => array_values($state['menu_top']) === [['key' => 'home'], ['key' => 'announcements'], ['key' => 'blog'], ['key' => 'departments']])
            ->fillForm(['menu_top' => [['key' => 'blog'], ['key' => 'home']], 'menu_tabs' => [['key' => 'home']]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['blog', 'home'], Menus::keys('top'));
        $this->assertSame(['home'], Menus::keys('tabs'));

        auth()->logout();
        $page = $this->get('/');
        $page->assertSeeInOrder(['aria-label="Main"', 'Articles', 'Home'], false);
        // A dock with one tab is no dock.
        $page->assertDontSee('class="dock lg:hidden"', false);
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
        $this->assertNull(Menus::toStore('top', ['home', 'announcements', 'blog', 'departments']));
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

        $this->get('/')->assertSee('หน้าแรก')->assertSee('บทความ')->assertSee('aria-label="เมนูหลัก"', false);
    }
}
