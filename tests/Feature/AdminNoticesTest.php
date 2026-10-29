<?php

namespace Tests\Feature;

use App\Filament\Auth\Login;
use App\Filament\Resources\Announcements\Pages\EditAnnouncement;
use App\Filament\Widgets\RecentActivity;
use App\Mail\StaffActivityDigest;
use App\Models\AccessLog;
use App\Models\Announcement;
use App\Models\AnnouncementType;
use App\Models\Department;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/** What the administrators are told of what others do in the panel: the bell, the morning email, the dashboard card. */
class AdminNoticesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_department_changing_or_deleting_a_post_that_shows_tells_the_administrators(): void
    {
        $admin = User::factory()->admin()->create();
        $dental = Department::factory()->create(['name' => 'Dental']);
        $desk = User::factory()->create(['department_id' => $dental->id]);
        $post = Announcement::factory()->create(['title' => 'Dental week', 'announcement_type_id' => AnnouncementType::where('slug', 'news')->value('id'), 'department_id' => $dental->id, 'status' => Announcement::STATUS_PUBLISHED]);

        // Where a department's change waits for approval, that already rings the bell; this is for where it does not.
        SiteSettings::save(['bchsp.security.department_approval' => false]);

        $this->actingAs($desk);
        Livewire::test(EditAnnouncement::class, ['record' => $post->getRouteKey()])->fillForm(['title' => 'Dental week, again'])->call('save')->assertHasNoFormErrors();

        $this->assertSame('A department changed a post that is showing', $admin->notifications()->sole()->data['title']);

        $post->fresh()->delete();
        $this->assertSame(2, $admin->notifications()->count());

        // An administrator's own changes ring nobody's bell.
        $this->actingAs($admin);
        $other = Announcement::factory()->create(['department_id' => $dental->id, 'status' => Announcement::STATUS_PUBLISHED]);
        $other->update(['title' => 'Changed by the admin']);
        $this->assertSame(2, $admin->notifications()->count());
    }

    public function test_five_failed_sign_ins_from_one_address_tell_the_administrators_once(): void
    {
        $admin = User::factory()->admin()->create();

        for ($i = 0; $i < 6; $i++) {
            Livewire::test(Login::class)->fillForm(['email' => 'nobody', 'password' => 'wrong'])->call('authenticate');
        }

        $this->assertSame('Many failed sign-ins', $admin->notifications()->sole()->data['title']);
    }

    public function test_the_morning_email_says_what_was_done_yesterday_and_nothing_on_a_quiet_day(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->create(['email' => 'admin@example.test']);

        $this->artisan('activity:daily')->assertSuccessful();
        Mail::assertNothingSent();

        $this->travel(-1)->days();
        AccessLog::record('login', userId: $admin->id);
        $this->travelBack();

        $this->artisan('activity:daily')->assertSuccessful();
        Mail::assertSent(StaffActivityDigest::class, fn (StaffActivityDigest $mail) => $mail->hasTo('admin@example.test') && $mail->lines->count() === 1);
    }

    public function test_the_dashboard_shows_the_latest_activity_to_administrators_only(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Somchai']);
        AccessLog::record('login', userId: $admin->id);

        $this->actingAs($admin);
        Livewire::test(RecentActivity::class)->assertSee('Somchai');

        $this->actingAs(User::factory()->create(['department_id' => Department::factory()->create()->id]));
        $this->assertFalse(RecentActivity::canView());
    }
}
