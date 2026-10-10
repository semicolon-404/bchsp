<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * The first administrator, made on the server: there is no sign-up page, and
 * the panel's "People" screen needs an administrator to open it.
 */
class CreateAdmin extends Command
{
    protected $signature = 'admin:create';

    protected $description = 'Create an administrator who can sign in to the panel';

    public function handle(): int
    {
        $values = [
            'name' => $this->ask('Name'),
            'email' => $this->ask('Email'),
            'password' => $this->secret('Password (at least 8 characters)'),
        ];

        $validator = Validator::make($values, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(8)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::create($values + ['role' => Role::Admin]);

        $this->info('Created. Sign in at '.url('/admin'));

        return self::SUCCESS;
    }
}
