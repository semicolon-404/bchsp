<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\TwoStep;
use Illuminate\Console\Command;

/**
 * The way back in for someone who lost the phone with their authenticator app
 * and has no recovery codes left. Run on the server, so only whoever can reach
 * the server can do it; the password is still needed to sign in afterwards.
 */
class TurnOffTwoStep extends Command
{
    protected $signature = 'two-step:off {email : The email of the account}';

    protected $description = 'Turn off two-step sign-in for one account (lost phone, no recovery codes)';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('No account has that email.');

            return self::FAILURE;
        }

        if (! TwoStep::isOnFor($user)) {
            $this->info('Two-step sign-in is not on for that account. Nothing changed.');

            return self::SUCCESS;
        }

        TwoStep::turnOffFor($user);

        $this->info("Two-step sign-in is off for {$user->email}. They sign in with the password alone, and can set it up again from the panel.");

        return self::SUCCESS;
    }
}
