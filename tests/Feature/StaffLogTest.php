<?php

namespace Tests\Feature;

use App\Filament\Auth\Login;
use App\Filament\Pages\SiteSettings as SettingsPage;
use App\Filament\Resources\AccessLogs\Pages\ManageAccessLogs;
use App\Filament\Resources\BlogCategories\Pages\ManageBlogCategories;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\AccessLog;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StaffLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_signing_in_and_a_failed_sign_in_are_written_down(): void
    {
        $user = User::factory()->create(['username' => 'malee']);

        Livewire::test(Login::class)->fillForm(['email' => 'malee', 'password' => 'wrong-password'])->call('authenticate');
        Livewire::test(Login::class)->fillForm(['email' => 'malee', 'password' => 'password'])->call('authenticate');

        $failed = AccessLog::where('event', 'login_failed')->sole();
        $this->assertSame('malee', $failed->identifier);
        $this->assertSame($user->id, AccessLog::where('event', 'login')->sole()->user_id);

        // The password that was typed is nowhere in the log.
        $this->assertStringNotContainsString('wrong-password', AccessLog::all()->toJson());
    }

    public function test_what_staff_create_change_and_delete_in_the_panel_is_written_down(): void
    {
        $this->actingAs($author = User::factory()->create());

        Livewire::test(ManageBlogCategories::class)->callAction('create', ['name' => 'Health', 'sort_order' => 0]);
        $category = BlogCategory::firstOrFail();

        Livewire::test(ManageBlogCategories::class)
            ->callAction(TestAction::make(EditAction::class)->table($category), ['name' => 'Health knowledge'])
            ->callAction(TestAction::make(DeleteAction::class)->table($category));

        $lines = AccessLog::where('event', 'like', 'admin.%')->orderBy('id')->get();

        $this->assertSame(['admin.created', 'admin.updated', 'admin.deleted'], $lines->pluck('event')->all());
        $this->assertSame(['blog_category'], $lines->pluck('subject_type')->unique()->values()->all());
        $this->assertSame([$author->id], $lines->pluck('user_id')->unique()->values()->all());
        // Which fields, not what they hold.
        $this->assertStringContainsString('name', $lines[1]->identifier);
        $this->assertStringNotContainsString('Health knowledge', $lines->toJson());
    }

    public function test_a_changed_password_is_noted_by_name_only(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $malee = User::factory()->create();

        Livewire::test(ManageUsers::class)
            ->callAction(TestAction::make(EditAction::class)->table($malee), ['password' => 'a-brand-new-password']);

        $line = AccessLog::where('event', 'admin.updated')->sole();

        $this->assertSame('password', $line->identifier);
        $this->assertStringNotContainsString($malee->fresh()->password, AccessLog::all()->toJson());
    }

    public function test_changed_settings_are_listed_by_name(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SettingsPage::class)->fillForm(['contact__phone' => '044 579 076'])->call('save');

        $line = AccessLog::where('event', 'admin.settings')->sole();

        $this->assertStringContainsString('contact.phone', $line->identifier);
        $this->assertStringNotContainsString('044 579 076', $line->identifier);
    }

    public function test_a_change_made_outside_the_panel_and_a_visit_to_the_site_leave_no_line(): void
    {
        $this->get('/')->assertOk();
        $this->get('/blog')->assertOk();

        // Signed in, but not from a screen of the panel: a script, a command.
        $this->actingAs(User::factory()->create());
        BlogPost::factory()->create()->update(['title' => 'Changed by a script']);

        $this->assertSame(0, AccessLog::count());
    }

    public function test_only_an_administrator_reads_the_log_and_nobody_can_alter_it(): void
    {
        AccessLog::record('login', userId: User::factory()->create(['name' => 'Malee'])->id);

        $this->actingAs(User::factory()->create())->get('/admin/access-logs')->assertForbidden();
        $this->actingAs($admin = User::factory()->admin()->create())->get('/admin/access-logs')->assertOk()->assertSee('Malee')->assertSee('Signed in');

        Livewire::test(ManageAccessLogs::class)
            ->assertActionDoesNotExist('create')
            ->assertActionDoesNotExist(TestAction::make(DeleteAction::class)->table(AccessLog::first()))
            ->assertActionDoesNotExist(TestAction::make(EditAction::class)->table(AccessLog::first()));
    }

    public function test_old_lines_are_cleared_but_never_before_ninety_days(): void
    {
        config(['bchsp.access_log.retention_days' => 1]);

        AccessLog::record('login');
        AccessLog::query()->update(['created_at' => now()->subDays(60)]);
        AccessLog::record('login');
        AccessLog::query()->latest('id')->limit(1)->update(['created_at' => now()->subDays(120)]);

        $this->artisan('model:prune', ['--model' => [AccessLog::class]]);

        $this->assertSame(1, AccessLog::count());
    }

    public function test_the_log_reads_in_thai(): void
    {
        app()->setLocale('th');
        AccessLog::record('login_failed', identifier: 'someone');

        $this->actingAs(User::factory()->admin()->create())->get('/admin/access-logs')
            ->assertSee('บันทึกการใช้งาน')
            ->assertSee('เข้าสู่ระบบไม่สำเร็จ');
    }
}
