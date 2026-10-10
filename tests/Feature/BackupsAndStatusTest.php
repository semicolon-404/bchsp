<?php

namespace Tests\Feature;

use App\Filament\Pages\SystemStatus;
use App\Models\User;
use App\Support\Backups;
use App\Support\SiteSettings;
use App\Support\SystemHealth;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;
use Tests\TestCase;

class BackupsAndStatusTest extends TestCase
{
    use RefreshDatabase;

    private string $folder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->folder = storage_path('framework/testing/backups-'.uniqid());
        config(['bchsp.backup.folder' => $this->folder, 'bchsp.backup.keep_days' => 14]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->folder);

        parent::tearDown();
    }

    /** Pretend the site is on MariaDB and that mysqldump writes a file, as it would on the server. */
    private function onMariaDb(bool $works = true): void
    {
        // The tests' own database stays as it is; only the backup is pointed elsewhere.
        config([
            'bchsp.backup.connection' => 'pretend',
            'database.connections.pretend' => ['driver' => 'mariadb', 'host' => '127.0.0.1', 'port' => '3306', 'username' => 'site', 'password' => 'a-secret', 'database' => 'bchsp_site'],
        ]);

        Process::fake(function ($process) use ($works) {
            if ($works) {
                $target = collect($process->command)->first(fn ($part) => str_starts_with($part, '--result-file='));
                file_put_contents(substr($target, strlen('--result-file=')), str_repeat("insert into settings values ('x');\n", 200));
            }

            return Process::result(errorOutput: $works ? '' : 'Access denied for user', exitCode: $works ? 0 : 1);
        });
    }

    public function test_the_database_is_copied_packed_and_the_password_kept_off_the_command_line(): void
    {
        $this->onMariaDb();

        $file = Backups::run();

        $this->assertFileExists($file);
        $this->assertStringEndsWith('.sql.gz', $file);
        $this->assertStringContainsString('insert into settings', (string) gzdecode(file_get_contents($file)));
        $this->assertFileDoesNotExist(substr($file, 0, -3));

        Process::assertRan(fn ($process) => ! str_contains(implode(' ', $process->command), 'a-secret')
            && ($process->environment['MYSQL_PWD'] ?? null) === 'a-secret'
            && in_array('--single-transaction', $process->command, true)
            && end($process->command) === 'bchsp_site');
    }

    public function test_a_copy_that_failed_says_why_and_leaves_nothing_behind(): void
    {
        $this->onMariaDb(works: false);

        $this->artisan('backup:database')->expectsOutputToContain('Access denied')->assertFailed();

        $this->assertNull(Backups::latest());
    }

    public function test_copies_older_than_the_days_kept_are_removed(): void
    {
        File::ensureDirectoryExists($this->folder);
        file_put_contents($old = $this->folder.DIRECTORY_SEPARATOR.'db-2026-01-01-031000.sql.gz', 'x');
        file_put_contents($new = $this->folder.DIRECTORY_SEPARATOR.'db-2026-10-09-031000.sql.gz', 'y');
        touch($old, now()->subDays(15)->getTimestamp());
        touch($new, now()->subDay()->getTimestamp());

        $this->assertSame(1, Backups::prune());
        $this->assertFileDoesNotExist($old);
        $this->assertSame(1, Backups::latest()['count']);
    }

    public function test_uploaded_files_are_mirrored_once_and_a_deleted_one_is_kept_for_the_days_kept(): void
    {
        $uploads = storage_path('app/public/_backup-test');
        File::ensureDirectoryExists($uploads);
        file_put_contents($uploads.'/notice.pdf', 'pdf');

        try {
            $first = Backups::mirrorUploads();
            $copy = $this->folder.'/uploads/_backup-test/notice.pdf';

            $this->assertGreaterThanOrEqual(1, $first['copied']);
            $this->assertFileExists($copy);
            $this->assertSame(0, Backups::mirrorUploads()['copied']);

            // Deleted from the site by mistake: still in the mirror...
            unlink($uploads.'/notice.pdf');
            Backups::mirrorUploads();
            $this->assertFileExists($copy);

            // ...until the days kept have passed.
            touch($copy, now()->subDays(15)->getTimestamp());
            $this->assertGreaterThanOrEqual(1, Backups::mirrorUploads()['removed']);
            $this->assertFileDoesNotExist($copy);
        } finally {
            File::deleteDirectory($uploads);
        }
    }

    public function test_the_copies_are_repeated_in_a_second_folder_off_the_disk(): void
    {
        $second = $this->folder.'-elsewhere';
        config(['bchsp.backup.copy_to' => $second]);
        $this->onMariaDb();

        try {
            $this->assertNull(Backups::latestElsewhere());

            $this->artisan('backup:database')->expectsOutputToContain('Second folder')->assertSuccessful();

            $copies = glob($second.DIRECTORY_SEPARATOR.'db-*.sql.gz');

            $this->assertCount(1, $copies);
            $this->assertSame(filesize(glob($this->folder.DIRECTORY_SEPARATOR.'db-*.sql.gz')[0]), filesize($copies[0]));
            $this->assertNotNull(Backups::latestElsewhere());
            // Run again: what is already there is not copied a second time.
            $this->assertSame(0, Backups::copyElsewhere()['copied']);

            // An old copy there goes after the days kept, like the ones here.
            touch($copies[0], now()->subDays(15)->getTimestamp());
            Backups::copyElsewhere();
            $this->assertFileDoesNotExist($copies[0]);
        } finally {
            File::deleteDirectory($second);
        }
    }

    public function test_without_a_second_folder_the_backup_says_the_copies_are_in_one_place(): void
    {
        $this->onMariaDb();

        $this->assertNull(Backups::copyElsewhere());
        $this->artisan('backup:database')->expectsOutputToContain('on this disk only')->assertSuccessful();
        $this->assertSame('warning', collect(SystemHealth::checks())->firstWhere('name', 'Backup kept elsewhere')['state']);
    }

    public function test_a_red_number_beside_system_status_says_how_many_things_are_wrong(): void
    {
        config(['app.debug' => true]);

        // Debug mode, no backup yet, scheduler never ran.
        $this->assertSame(3, SystemHealth::problems());
        $this->assertSame('3', SystemStatus::getNavigationBadge());

        config(['app.debug' => false]);
        SystemHealth::beat();
        File::ensureDirectoryExists($this->folder);
        file_put_contents($this->folder.DIRECTORY_SEPARATOR.'db-'.now()->format('Y-m-d-His').'.sql.gz', 'x');

        $this->assertNull(SystemStatus::getNavigationBadge());
    }

    public function test_the_backup_and_the_heartbeat_are_scheduled(): void
    {
        $events = collect(app(Schedule::class)->events());

        $backup = $events->first(fn ($event) => str_contains((string) $event->command, 'backup:database'));

        $this->assertSame('10 3 * * *', $backup->expression);
        $this->assertNotNull($events->first(fn ($event) => $event->description === 'system:heartbeat' && $event->expression === '* * * * *'));
    }

    public function test_the_status_page_says_what_is_wrong_and_what_to_do(): void
    {
        config(['app.debug' => true]);
        SiteSettings::save(['maintenance.enabled' => true]);

        $checks = collect(SystemHealth::checks())->keyBy('name');

        $this->assertSame('ok', $checks['Database']['state']);
        $this->assertSame('problem', $checks['Scheduled work']['state']);
        $this->assertSame('problem', $checks['Database backup']['state']);
        $this->assertSame('problem', $checks['Debug mode']['state']);
        $this->assertSame('warning', $checks['Maintenance mode']['state']);
        $this->assertSame('warning', $checks['Two-step sign-in']['state']);
        $this->assertStringContainsString('schedule:run', $checks['Scheduled work']['advice']);
        // Nothing to do about what is right.
        $this->assertNull($checks['Database']['advice']);
    }

    public function test_the_checks_turn_green_when_things_are_right(): void
    {
        config(['app.debug' => false, 'bchsp.security.staff_two_step' => true]);
        SystemHealth::beat();
        File::ensureDirectoryExists($this->folder);
        file_put_contents($this->folder.DIRECTORY_SEPARATOR.'db-'.now()->format('Y-m-d-His').'.sql.gz', 'x');

        $checks = collect(SystemHealth::checks())->keyBy('name');

        foreach (['Scheduled work', 'Database backup', 'Debug mode', 'Maintenance mode', 'Two-step sign-in'] as $name) {
            $this->assertSame('ok', $checks[$name]['state'], $name);
        }

        // Three missed minutes and the scheduler counts as stopped.
        $this->travel(4)->minutes();
        $this->assertFalse(SystemHealth::schedulerIsRunning());
    }

    public function test_only_an_administrator_sees_the_status_page_and_can_take_a_backup_from_it(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/system-status')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/admin/system-status')->assertOk()->assertSee('Database backup')->assertSee('Scheduled work');

        $this->onMariaDb();

        Livewire::test(SystemStatus::class)->callAction('backupNow')->assertNotified('Backup taken');

        $this->assertNotNull(Backups::latest());
    }

    public function test_the_status_page_reads_in_thai(): void
    {
        app()->setLocale('th');

        $this->actingAs(User::factory()->admin()->create())->get('/admin/system-status')
            ->assertSee('สถานะระบบ')
            ->assertSee('การสำรองฐานข้อมูล')
            ->assertSee('สำรองข้อมูลตอนนี้');
    }
}
