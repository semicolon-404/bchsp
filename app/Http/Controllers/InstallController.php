<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Throwable;

/**
 * Setting the site up from a browser, for a server where no command can be typed (shared hosting):
 * /install?key=... makes the tables, puts in the starting content, links the uploads folder and creates
 * the first administrator: what docs/DEPLOY.th.md does with "php artisan" on a server that has a terminal.
 *
 * It answers only while INSTALL_KEY is set in .env and the address carries that key, and only until an
 * administrator exists: after that it is a page that is not there. It runs outside the "web" middleware:
 * before the tables exist there is no session to start.
 */
class InstallController extends Controller
{
    public function show(Request $request): Response
    {
        $this->guard($request);

        return response()->view('install', ['key' => $request->query('key'), 'checks' => $this->checks(), 'errors' => [], 'old' => [], 'steps' => null]);
    }

    public function run(Request $request): Response
    {
        $this->guard($request);

        $values = $request->only(['name', 'username', 'password']);
        $validator = Validator::make($values, [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'alpha_dash', 'max:50'],
            'password' => ['required', Password::min(8)],
        ]);

        if ($validator->fails()) {
            return response()->view('install', ['key' => $request->input('key'), 'checks' => $this->checks(), 'errors' => $validator->errors()->all(), 'old' => $values, 'steps' => null], 422);
        }

        // One after another; the first that fails stops the rest, and says why.
        $steps = [];
        $failed = false;

        foreach ([
            'Make the tables' => fn () => $this->artisan('migrate', ['--force' => true]),
            'Put in the starting content' => fn () => $this->artisan('db:seed', ['--force' => true]),
            'Link the folder of uploaded files' => fn () => file_exists(public_path('storage')) ? 'Already there.' : $this->artisan('storage:link'),
            'Create the administrator' => function () use ($values) {
                User::create($values + ['role' => Role::Admin]);

                return 'Sign in as "'.$values['username'].'".';
            },
            'Clear what was kept from before' => fn () => $this->artisan('optimize:clear'),
        ] as $name => $step) {
            if ($failed) {
                $steps[] = ['name' => $name, 'state' => 'skipped', 'said' => ''];

                continue;
            }

            try {
                $steps[] = ['name' => $name, 'state' => 'done', 'said' => (string) $step()];
            } catch (Throwable $e) {
                $steps[] = ['name' => $name, 'state' => 'failed', 'said' => $e->getMessage()];
                $failed = true;
            }
        }

        return response()->view('install', ['key' => $request->input('key'), 'checks' => $this->checks(), 'errors' => [], 'old' => [], 'steps' => $steps, 'failed' => $failed], $failed ? 500 : 200);
    }

    /** Only with the key from .env, and only until the site has an administrator. */
    private function guard(Request $request): void
    {
        $key = (string) config('bchsp.install_key');
        $given = $request->input('key', $request->query('key'));

        abort_unless(strlen($key) >= 16 && is_string($given) && hash_equals($key, $given) && ! $this->installed(), 404);
    }

    private function installed(): bool
    {
        try {
            return Schema::hasTable('users') && User::where('role', Role::Admin)->exists();
        } catch (Throwable) {
            return false;
        }
    }

    private function artisan(string $command, array $arguments = []): string
    {
        $code = Artisan::call($command, $arguments);
        $said = trim(Artisan::output());

        if ($code !== 0) {
            throw new \RuntimeException($said !== '' ? $said : "\"$command\" did not finish.");
        }

        return $said;
    }

    /**
     * What must be right before setting up, each with whether it is.
     *
     * @return list<array{name: string, ok: bool, found: string}>
     */
    private function checks(): array
    {
        $database = function (): array {
            try {
                DB::connection()->getPdo();

                return [true, (string) DB::connection()->getDatabaseName()];
            } catch (Throwable $e) {
                return [false, $e->getMessage()];
            }
        };
        [$connects, $found] = $database();
        $missing = array_values(array_filter(['pdo_mysql', 'mbstring', 'intl', 'gd', 'zip', 'fileinfo', 'exif', 'openssl'], fn (string $extension) => ! extension_loaded($extension)));

        return [
            ['name' => 'PHP 8.2 or newer', 'ok' => PHP_VERSION_ID >= 80200, 'found' => PHP_VERSION],
            ['name' => 'PHP extensions', 'ok' => $missing === [], 'found' => $missing === [] ? 'All there' : 'Missing: '.implode(', ', $missing)],
            ['name' => 'The database answers', 'ok' => $connects, 'found' => $found],
            ['name' => 'The storage folder can be written to', 'ok' => is_writable(storage_path()) && is_writable(storage_path('framework')), 'found' => storage_path()],
            ['name' => 'Errors are hidden from visitors (APP_DEBUG=false)', 'ok' => ! config('app.debug'), 'found' => config('app.debug') ? 'APP_DEBUG is on' : 'Off'],
        ];
    }
}
