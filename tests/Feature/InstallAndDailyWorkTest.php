<?php

namespace Tests\Feature;

use App\Filament\Pages\SystemStatus;
use App\Models\User;
use App\Support\SystemHealth;
use App\Support\WebCron;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

/** A server where no command can be typed: setting up from a browser, updating from the panel, and daily work done on visits. */
class InstallAndDailyWorkTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'a-long-random-install-key';

    public function test_there_is_no_install_page_without_the_key_from_the_servers_own_file(): void
    {
        // No key set: no such page, whatever is asked.
        $this->get('/install')->assertNotFound();
        $this->get('/install?key='.self::KEY)->assertNotFound();

        config(['bchsp.install_key' => self::KEY]);

        $this->get('/install')->assertNotFound();
        $this->get('/install?key=wrong')->assertNotFound();
        $this->get('/install?key[]=x')->assertNotFound();
        $this->post('/install', ['key' => 'wrong', 'name' => 'A', 'username' => 'a', 'password' => 'long-enough'])->assertNotFound();

        // A key too short to be a secret opens nothing either.
        config(['bchsp.install_key' => 'short']);
        $this->get('/install?key=short')->assertNotFound();
    }

    public function test_the_site_is_set_up_from_a_browser_and_the_page_then_closes(): void
    {
        config(['bchsp.install_key' => self::KEY]);

        $this->get('/install?key='.self::KEY)->assertOk()->assertSee('PHP 8.2')->assertSee('name="username"', false);

        // What is typed is checked before anything is done.
        $this->post('/install', ['key' => self::KEY, 'name' => 'Somchai', 'username' => 'has space', 'password' => 'short'])->assertStatus(422);
        $this->assertSame(0, User::count());

        $this->post('/install', ['key' => self::KEY, 'name' => 'Somchai', 'username' => 'somchai', 'password' => 'a-long-password'])
            ->assertOk()
            ->assertSee('ติดตั้งเสร็จแล้ว')
            ->assertSee('INSTALL_KEY');

        $admin = User::where('username', 'somchai')->firstOrFail();

        $this->assertTrue($admin->isAdmin());
        $this->assertNotSame('a-long-password', $admin->password);

        // With an administrator the page is gone, key or no key: nobody makes themselves a second one.
        $this->get('/install?key='.self::KEY)->assertNotFound();
        $this->post('/install', ['key' => self::KEY, 'name' => 'Mallory', 'username' => 'mallory', 'password' => 'a-long-password'])->assertNotFound();
        $this->assertSame(1, User::count());
    }

    public function test_an_administrator_updates_the_database_from_the_panel_after_new_files(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SystemStatus::class)->callAction('updateDatabase')->assertNotified('The website is up to date');

        $this->actingAs(User::factory()->create())->get('/admin/system-status')->assertForbidden();
    }

    public function test_where_there_is_no_scheduler_the_days_work_is_done_on_a_visit(): void
    {
        Artisan::command('test:noop', fn () => 0);

        // Switched off (the usual server has a real scheduler): a visit does nothing.
        $this->travelTo('2026-11-10 09:00');
        $this->get('/')->assertOk();
        $this->assertSame([], WebCron::tick());
        $this->assertNull(SystemHealth::schedulerLastRan());

        config(['bchsp.schedule_on_visits' => true]);
        Cache::flush();

        // 02:00: only the clean-up of the access log is due.
        $this->travelTo('2026-11-10 02:00');
        $this->assertSame(['prune'], WebCron::tick());
        $this->assertNotNull(SystemHealth::schedulerLastRan());

        // The same minute, another visitor: nothing twice.
        $this->assertSame([], WebCron::tick());

        // A visit at 09:00 the same day: what has come due since, and not the first again.
        $this->travelTo('2026-11-10 09:00');
        Cache::forget('webcron:tick');
        config(['bchsp.backup.connection' => 'nowhere']);   // the backup itself fails on the tests' database: it is still marked as tried
        $this->assertSame(['backup', 'daily', 'notify', 'report'], WebCron::tick());

        // The next day at one in the morning: nothing is due yet; at four, the night's work again.
        $this->travelTo('2026-11-11 00:10');
        Cache::forget('webcron:tick');
        $this->assertSame([], WebCron::tick());

        $this->travelTo('2026-11-11 04:00');
        Cache::forget('webcron:tick');
        $this->assertSame(['prune', 'backup'], WebCron::tick());
    }

    public function test_a_visit_starts_the_days_work_only_after_the_page_has_been_sent(): void
    {
        config(['bchsp.schedule_on_visits' => true]);
        $this->travelTo('2026-11-10 00:05');

        $this->get('/')->assertOk();

        // The heartbeat System status reads was set by that visit.
        $this->assertNotNull(SystemHealth::schedulerLastRan());
    }
}
