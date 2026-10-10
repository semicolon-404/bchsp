<?php

namespace Tests\Feature;

use App\Filament\Auth\Login;
use App\Models\User;
use App\Support\TwoStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TwoStepSignInTest extends TestCase
{
    use RefreshDatabase;

    private function withTwoStep(User $user): User
    {
        $user->saveAppAuthenticationSecret(TwoStep::provider()->generateSecret());

        return $user;
    }

    public function test_while_it_is_required_someone_without_it_is_sent_to_set_it_up(): void
    {
        config(['bchsp.security.staff_two_step' => true]);

        $this->actingAs(User::factory()->create())->get('/admin')
            ->assertRedirect(route('filament.admin.auth.multi-factor-authentication.set-up-required'));
    }

    public function test_the_set_up_page_opens(): void
    {
        config(['bchsp.security.staff_two_step' => true]);

        $this->actingAs(User::factory()->create())
            ->get(route('filament.admin.auth.multi-factor-authentication.set-up-required'))
            ->assertOk();
    }

    public function test_while_it_is_required_someone_who_has_it_uses_the_panel(): void
    {
        config(['bchsp.security.staff_two_step' => true]);

        $this->actingAs($this->withTwoStep(User::factory()->create()))->get('/admin')->assertOk();
    }

    public function test_while_it_is_not_required_nobody_is_sent_anywhere(): void
    {
        config(['bchsp.security.staff_two_step' => false]);

        $this->actingAs(User::factory()->create())->get('/admin')->assertOk();
    }

    public function test_it_is_required_unless_the_environment_says_otherwise(): void
    {
        $this->assertStringContainsString("env('STAFF_TWO_STEP', true)", file_get_contents(config_path('bchsp.php')));
    }

    public function test_the_password_alone_does_not_sign_in_an_account_that_has_it(): void
    {
        $user = $this->withTwoStep(User::factory()->create(['email' => 'nurse@example.com']));

        Livewire::test(Login::class)
            ->fillForm(['email' => 'nurse@example.com', 'password' => 'password'])
            ->call('authenticate');

        $this->assertGuest();

        Livewire::test(Login::class)
            ->fillForm(['email' => 'nurse@example.com', 'password' => 'password'])
            ->call('authenticate')
            ->fillForm([TwoStep::provider()->getId() => ['code' => '000000']], 'multiFactorChallengeForm')
            ->call('authenticate');

        $this->assertGuest();
    }

    public function test_the_password_and_the_code_from_the_app_sign_in(): void
    {
        $user = $this->withTwoStep(User::factory()->create(['email' => 'nurse@example.com']));

        Livewire::test(Login::class)
            ->fillForm(['email' => 'nurse@example.com', 'password' => 'password'])
            ->call('authenticate')
            ->fillForm([TwoStep::provider()->getId() => ['code' => TwoStep::provider()->getCurrentCode($user)]], 'multiFactorChallengeForm')
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_the_secret_is_never_part_of_the_account_as_data(): void
    {
        $user = $this->withTwoStep(User::factory()->create());

        $this->assertArrayNotHasKey('app_authentication_secret', $user->fresh()->toArray());
        $this->assertArrayNotHasKey('app_authentication_recovery_codes', $user->fresh()->toArray());
        // Encrypted where it is kept.
        $this->assertNotSame($user->getAppAuthenticationSecret(), $user->fresh()->getRawOriginal('app_authentication_secret'));
    }

    public function test_someone_who_lost_their_phone_is_let_back_in_from_the_server(): void
    {
        $user = $this->withTwoStep(User::factory()->create(['email' => 'nurse@example.com']));

        $this->artisan('two-step:off', ['account' => 'nurse@example.com'])->assertSuccessful();

        $this->assertFalse(TwoStep::isOnFor($user->fresh()));
        $this->artisan('two-step:off', ['account' => 'nobody@example.com'])->assertFailed();

        // An account with a username and no email is found by its username.
        $other = $this->withTwoStep(User::factory()->create(['username' => 'malee', 'email' => null]));
        $this->artisan('two-step:off', ['account' => 'malee'])->assertSuccessful();
        $this->assertFalse(TwoStep::isOnFor($other->fresh()));
    }
}
