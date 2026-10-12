<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use PDO;
use RuntimeException;
use Throwable;

/**
 * A copy of the database, taken every night (routes/console.php) and kept for
 * two weeks in storage/app/backups, outside anything the web server hands out.
 *
 * The uploaded pictures and documents are files, not database rows:
 * mirrorUploads() keeps a second copy of them in the same folder, with the same limit.
 *
 * That folder is on the same disk as the site: a dead disk or a stolen server
 * takes the copies with it. So copyElsewhere() repeats both in a second folder,
 * named in BACKUP_COPY_TO: another disk, a network share, or a folder that a
 * sync program (Google Drive for desktop, OneDrive) sends off the machine.
 * Until that is set, the System status page says the copies are in one place only.
 *
 * The copy is made by the mysqldump program where the server lets PHP run programs. Shared hosting
 * often does not (proc_open switched off): there, or with BACKUP_MYSQLDUMP=php, the site writes the
 * copy itself, table by table (writeItself()). Either copy is brought back the same way.
 *
 * To bring one back:  gunzip -c db-....sql.gz | mysql -u USER -p DATABASE
 * (or unpack it and import the .sql file in phpMyAdmin)
 */
class Backups
{
    /** @return string the file that was written */
    public static function run(): string
    {
        $connection = config('database.connections.'.(config('bchsp.backup.connection') ?: config('database.default')));

        if (! in_array($connection['driver'] ?? null, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('Backups are written for MySQL and MariaDB; this site uses "'.($connection['driver'] ?? '?').'".');
        }

        File::ensureDirectoryExists(self::folder());

        $plain = self::folder().DIRECTORY_SEPARATOR.'db-'.now()->format('Y-m-d-His').'.sql';

        if (self::writesItself()) {
            try {
                self::writeItself($connection, $plain);
            } catch (Throwable $e) {
                @unlink($plain);

                throw new RuntimeException('The database could not be copied: '.$e->getMessage(), previous: $e);
            }

            $packed = self::pack($plain);
            self::prune();

            return $packed;
        }

        // The password travels in the environment of that one process, not on the command line where other
        // users of the server could read it. --single-transaction: a consistent copy without locking the site.
        $result = Process::timeout(1800)
            ->env(['MYSQL_PWD' => (string) ($connection['password'] ?? '')])
            ->run([
                config('bchsp.backup.mysqldump'),
                '--host='.$connection['host'],
                '--port='.$connection['port'],
                '--user='.$connection['username'],
                '--single-transaction',
                '--quick',
                '--routines',
                '--default-character-set=utf8mb4',
                '--result-file='.$plain,
                $connection['database'],
            ]);

        if ($result->failed() || ! is_file($plain) || filesize($plain) === 0) {
            @unlink($plain);

            throw new RuntimeException('The database could not be copied: '.(trim($result->errorOutput()) ?: 'mysqldump wrote nothing. Is BACKUP_MYSQLDUMP its right path?'));
        }

        $packed = self::pack($plain);
        self::prune();

        return $packed;
    }

    /** Whether the site writes the copy itself: asked for (BACKUP_MYSQLDUMP=php), or the server does not let PHP run programs. */
    public static function writesItself(): bool
    {
        $program = trim((string) config('bchsp.backup.mysqldump'));

        return $program === '' || strtolower($program) === 'php' || ! function_exists('proc_open');
    }

    /**
     * Write the whole database as SQL, without any program but PHP: every table's definition, then its rows.
     *
     * On a connection of its own, so that what it sets does not reach the rest of the request: one view of
     * the data from start to end (a consistent snapshot, without locking the site), times in UTC as
     * mysqldump writes them, and rows read one at a time so that a large table does not fill the memory.
     *
     * @param  array<string, mixed>  $connection
     */
    private static function writeItself(array $connection, string $plain): void
    {
        config(['database.connections.backup-copy' => ['charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci'] + $connection]);
        DB::purge('backup-copy');

        $pdo = DB::connection('backup-copy')->getPdo();
        $out = fopen($plain, 'wb');

        try {
            $pdo->exec("SET NAMES utf8mb4, time_zone = '+00:00'");
            $pdo->exec('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            $pdo->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT');

            fwrite($out, '-- '.$connection['database'].', copied '.now()->toIso8601String()." by the site itself\n"
                ."SET NAMES utf8mb4;\nSET time_zone = '+00:00';\nSET FOREIGN_KEY_CHECKS = 0;\nSET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n");

            $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);

            foreach ($tables as [$table]) {
                $name = '`'.str_replace('`', '``', $table).'`';

                fwrite($out, "DROP TABLE IF EXISTS $name;\n".$pdo->query("SHOW CREATE TABLE $name")->fetch(PDO::FETCH_NUM)[1].";\n\n");

                // Bytes that are not text (a blob, a binary key) are written as hexadecimal: no character set can spoil them.
                $binary = [];

                foreach ($pdo->query("SHOW COLUMNS FROM $name")->fetchAll(PDO::FETCH_ASSOC) as $column) {
                    if (preg_match('/blob|binary|^bit/i', $column['Type'])) {
                        $binary[$column['Field']] = true;
                    }
                }

                $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
                $rows = $pdo->query("SELECT * FROM $name");
                $batch = [];
                $size = 0;

                while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
                    $values = [];

                    foreach ($row as $field => $value) {
                        $values[] = match (true) {
                            $value === null => 'NULL',
                            isset($binary[$field]) => $value === '' ? "''" : '0x'.bin2hex($value),
                            default => $pdo->quote((string) $value),
                        };
                    }

                    $batch[] = '('.implode(',', $values).')';
                    $size += strlen(end($batch));

                    // Statements of about half a megabyte: quick to bring back, and under any server's limit.
                    if ($size > 512 * 1024) {
                        fwrite($out, "INSERT INTO $name VALUES\n".implode(",\n", $batch).";\n");
                        $batch = [];
                        $size = 0;
                    }
                }

                $rows->closeCursor();
                $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

                if ($batch !== []) {
                    fwrite($out, "INSERT INTO $name VALUES\n".implode(",\n", $batch).";\n");
                }

                fwrite($out, "\n");
            }

            fwrite($out, "SET FOREIGN_KEY_CHECKS = 1;\n");
            $pdo->exec('COMMIT');

            if ($tables === []) {
                throw new RuntimeException('the database has no tables.');
            }
        } finally {
            fclose($out);
            DB::purge('backup-copy');
        }
    }

    /** Squeeze the copy (text shrinks to a fifth or less) and remove the plain one. */
    private static function pack(string $plain): string
    {
        $packed = $plain.'.gz';
        $in = fopen($plain, 'rb');
        $out = gzopen($packed, 'wb6');

        while (! feof($in)) {
            gzwrite($out, fread($in, 1024 * 512));
        }

        fclose($in);
        gzclose($out);
        unlink($plain);

        return $packed;
    }

    /** Remove copies older than the days kept. Returns how many went. */
    public static function prune(): int
    {
        $before = now()->subDays(max(1, (int) config('bchsp.backup.keep_days')))->getTimestamp();
        $removed = 0;

        foreach (self::files() as $file) {
            if (filemtime($file) < $before) {
                unlink($file);
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * The newest copy: when it was made, how big it is, and how many there are. Null when there is none.
     *
     * @return array{at: Carbon, bytes: int, count: int}|null
     */
    public static function latest(): ?array
    {
        $files = self::files();

        if ($files === []) {
            return null;
        }

        usort($files, fn (string $a, string $b) => filemtime($b) <=> filemtime($a));

        return ['at' => Carbon::createFromTimestamp(filemtime($files[0]), config('app.timezone')), 'bytes' => (int) filesize($files[0]), 'count' => count($files)];
    }

    /**
     * Keep a second copy of every uploaded file (pictures, PDFs) beside the database copies.
     *
     * A mirror, not an archive: a file is copied once, when it is new or has changed. A file that has gone
     * from the site stays in the mirror for the days kept and is then removed, so a mistaken deletion
     * can be undone for two weeks without the mirror growing for ever. Files still being uploaded are skipped.
     *
     * @return array{copied: int, removed: int}
     */
    public static function mirrorUploads(): array
    {
        $from = storage_path('app'.DIRECTORY_SEPARATOR.'public');
        $to = self::folder().DIRECTORY_SEPARATOR.'uploads';
        $before = now()->subDays(max(1, (int) config('bchsp.backup.keep_days')))->getTimestamp();
        $copied = $removed = 0;

        File::ensureDirectoryExists($to);

        foreach (is_dir($from) ? File::allFiles($from) : [] as $file) {
            $relative = $file->getRelativePathname();

            if (str_starts_with($relative, 'livewire-tmp'.DIRECTORY_SEPARATOR)) {
                continue;
            }

            $copy = $to.DIRECTORY_SEPARATOR.$relative;

            if (! is_file($copy) || filesize($copy) !== $file->getSize() || filemtime($copy) < $file->getMTime()) {
                File::ensureDirectoryExists(dirname($copy));
                copy($file->getPathname(), $copy);
                $copied++;
            }
        }

        // Gone from the site: kept here for the days kept, counted from when the copy was last written.
        foreach (File::allFiles($to) as $copy) {
            if (! is_file($from.DIRECTORY_SEPARATOR.$copy->getRelativePathname()) && $copy->getMTime() < $before) {
                unlink($copy->getPathname());
                $removed++;
            }
        }

        return ['copied' => $copied, 'removed' => $removed];
    }

    /** The second folder the copies are repeated in, or null when none has been named. */
    public static function elsewhere(): ?string
    {
        $folder = config('bchsp.backup.copy_to');

        return filled($folder) ? rtrim((string) $folder, '/\\') : null;
    }

    /**
     * Repeat the newest database copy and the mirror of the uploads in the second folder, and
     * remove database copies there that are older than the days kept.
     *
     * @return array{copied: int}|null null when no second folder has been named
     */
    public static function copyElsewhere(): ?array
    {
        if (! $to = self::elsewhere()) {
            return null;
        }

        File::ensureDirectoryExists($to);

        if (! is_writable($to)) {
            throw new RuntimeException("The second backup folder cannot be written to: $to");
        }

        $copied = 0;

        foreach (self::files() as $file) {
            $copy = $to.DIRECTORY_SEPARATOR.basename($file);

            if (! is_file($copy) || filesize($copy) !== filesize($file)) {
                copy($file, $copy);
                touch($copy, filemtime($file));
                $copied++;
            }
        }

        $before = now()->subDays(max(1, (int) config('bchsp.backup.keep_days')))->getTimestamp();

        foreach (glob($to.DIRECTORY_SEPARATOR.'db-*.sql.gz') ?: [] as $copy) {
            if (filemtime($copy) < $before) {
                unlink($copy);
            }
        }

        $uploads = self::folder().DIRECTORY_SEPARATOR.'uploads';

        foreach (is_dir($uploads) ? File::allFiles($uploads) : [] as $file) {
            $copy = $to.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.$file->getRelativePathname();

            if (! is_file($copy) || filesize($copy) !== $file->getSize()) {
                File::ensureDirectoryExists(dirname($copy));
                copy($file->getPathname(), $copy);
                $copied++;
            }
        }

        return ['copied' => $copied];
    }

    /** When the newest database copy in the second folder was made, or null when there is none. */
    public static function latestElsewhere(): ?Carbon
    {
        $files = self::elsewhere() ? (glob(self::elsewhere().DIRECTORY_SEPARATOR.'db-*.sql.gz') ?: []) : [];

        return $files === [] ? null : Carbon::createFromTimestamp(max(array_map('filemtime', $files)), config('app.timezone'));
    }

    public static function folder(): string
    {
        return config('bchsp.backup.folder') ?: storage_path('app'.DIRECTORY_SEPARATOR.'backups');
    }

    /** @return list<string> */
    private static function files(): array
    {
        return is_dir(self::folder()) ? (glob(self::folder().DIRECTORY_SEPARATOR.'db-*.sql.gz') ?: []) : [];
    }
}
