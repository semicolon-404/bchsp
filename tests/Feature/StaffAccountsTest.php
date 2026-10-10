<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class StaffAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_an_administrator_reaches_the_staff_screen(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/users')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/admin/users')->assertOk();
    }

    public function test_an_author_does_not_see_staff_in_the_menu(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertOk()->assertDontSee('/admin/users');
    }

    public function test_an_administrator_sees_staff_in_the_menu(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertOk()->assertSee('/admin/users');
    }

    public function test_an_administrator_adds_an_author(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageUsers::class)
            ->callAction('create', [
                'name' => 'Malee',
                'email' => 'malee@example.com',
                'role' => Role::Author->value,
                'password' => 'a-long-password',
            ])
            ->assertHasNoFormErrors();

        $malee = User::where('email', 'malee@example.com')->firstOrFail();

        $this->assertSame(Role::Author, $malee->role);
        $this->assertTrue(Hash::check('a-long-password', $malee->password));
    }

    public function test_a_short_password_or_a_used_email_is_refused(): void
    {
        $this->actingAs(User::factory()->admin()->create(['email' => 'boss@example.com']));

        Livewire::test(ManageUsers::class)
            ->callAction('create', [
                'name' => 'Malee',
                'email' => 'boss@example.com',
                'role' => Role::Author->value,
                'password' => 'short',
            ])
            ->assertHasFormErrors(['email', 'password']);

        $this->assertSame(1, User::count());
    }

    public function test_editing_without_a_password_keeps_the_old_one(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $malee = User::factory()->create(['password' => 'the-old-password']);

        Livewire::test(ManageUsers::class)
            ->callAction(TestAction::make(EditAction::class)->table($malee), ['name' => 'Malee S.', 'role' => Role::Admin->value, 'password' => null])
            ->assertHasNoFormErrors();

        $malee->refresh();

        $this->assertSame('Malee S.', $malee->name);
        $this->assertTrue($malee->isAdmin());
        $this->assertTrue(Hash::check('the-old-password', $malee->password));
    }

    public function test_an_administrator_cannot_demote_or_delete_themselves(): void
    {
        $this->actingAs($admin = User::factory()->admin()->create());
        $other = User::factory()->create();

        Livewire::test(ManageUsers::class)
            ->callAction(TestAction::make(EditAction::class)->table($admin), ['role' => Role::Author->value])
            ->assertHasNoFormErrors()
            ->assertActionHidden(TestAction::make(DeleteAction::class)->table($admin))
            ->assertActionVisible(TestAction::make(DeleteAction::class)->table($other));

        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_an_administrator_removes_someone_who_left(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $gone = User::factory()->create();

        Livewire::test(ManageUsers::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($gone));

        $this->assertModelMissing($gone);
    }

    public function test_the_staff_screen_reads_in_thai(): void
    {
        app()->setLocale('th');

        $this->actingAs(User::factory()->admin()->create())->get('/admin/users')
            ->assertOk()
            ->assertSee('เจ้าหน้าที่')
            ->assertSee('บทบาท');
    }
}
