<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StaffAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_account_is_an_author(): void
    {
        $user = User::factory()->create();

        $this->assertSame(Role::Author, $user->fresh()->role);
        $this->assertFalse($user->isAdmin());
        $this->assertTrue(User::factory()->admin()->create()->isAdmin());
    }

    public function test_authors_and_administrators_can_open_the_panel(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertOk();
    }

    public function test_staff_sign_in_with_email_and_password(): void
    {
        $user = User::factory()->create(['email' => 'nurse@example.com']);

        Livewire::test(Login::class)
            ->fillForm(['email' => 'nurse@example.com', 'password' => 'password'])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_wrong_password_does_not_sign_anyone_in(): void
    {
        User::factory()->create(['email' => 'nurse@example.com']);

        Livewire::test(Login::class)
            ->fillForm(['email' => 'nurse@example.com', 'password' => 'not-it'])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest();
    }

    public function test_nobody_can_sign_up(): void
    {
        $this->get('/register')->assertNotFound();
        $this->get('/admin/register')->assertNotFound();
        $this->post('/register', ['email' => 'a@example.com', 'password' => 'password'])->assertNotFound();
    }

    public function test_the_first_administrator_is_made_on_the_server(): void
    {
        $this->artisan('admin:create')
            ->expectsQuestion('Name', 'Somchai')
            ->expectsQuestion('Email', 'somchai@example.com')
            ->expectsQuestion('Password (at least 8 characters)', 'a-long-password')
            ->assertSuccessful();

        $admin = User::where('email', 'somchai@example.com')->firstOrFail();

        $this->assertTrue($admin->isAdmin());
        $this->assertTrue(password_verify('a-long-password', $admin->password));
    }

    public function test_an_administrator_is_not_made_from_a_short_password_or_a_used_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->artisan('admin:create')
            ->expectsQuestion('Name', 'Somchai')
            ->expectsQuestion('Email', 'taken@example.com')
            ->expectsQuestion('Password (at least 8 characters)', 'short')
            ->assertFailed();

        $this->assertSame(1, User::count());
    }
}
