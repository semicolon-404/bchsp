<?php

namespace Tests\Feature;

use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Filament\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Resources\Announcements\Pages\EditAnnouncement;
use App\Filament\Resources\Announcements\Pages\ListAnnouncements;
use App\Filament\Resources\ClinicSessions\Pages\ManageClinicSessions;
use App\Filament\Resources\Departments\Pages\ManageDepartments;
use App\Filament\Resources\People\Pages\ManagePeople;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\Announcement;
use App\Models\AnnouncementType;
use App\Models\ClinicSession;
use App\Models\Department;
use App\Models\Person;
use App\Models\User;
use App\Models\WorkGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** The account of one department: it posts on that department's board and keeps its page, and reaches nothing else. */
class DepartmentAccountsTest extends TestCase
{
    use RefreshDatabase;

    private Department $dental;

    private Department $pharmacy;

    private User $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dental = Department::factory()->create(['name' => 'Dental']);
        $this->pharmacy = Department::factory()->create(['name' => 'Pharmacy']);
        $this->account = User::factory()->create(['name' => 'Dental desk', 'department_id' => $this->dental->id]);
    }

    public function test_an_author_with_a_department_is_that_departments_account_and_an_administrator_never_is(): void
    {
        $this->assertTrue($this->account->postsForOneDepartment());
        $this->assertFalse(User::factory()->create()->postsForOneDepartment());
        $this->assertFalse(User::factory()->admin()->create(['department_id' => $this->dental->id])->postsForOneDepartment());
    }

    public function test_it_sees_only_what_its_department_put_out(): void
    {
        $own = Announcement::factory()->create(['title' => 'Brush twice a day', 'department_id' => $this->dental->id]);
        $other = Announcement::factory()->create(['title' => 'Medicine by post', 'department_id' => $this->pharmacy->id]);
        $central = Announcement::factory()->create(['title' => 'Notice of the whole hospital']);

        $this->actingAs($this->account);

        Livewire::test(ListAnnouncements::class)->assertCanSeeTableRecords([$own])->assertCanNotSeeTableRecords([$other, $central]);

        $this->get(AnnouncementResource::getUrl('edit', ['record' => $own]))->assertOk();
        $this->get(AnnouncementResource::getUrl('edit', ['record' => $other]))->assertNotFound();
        $this->get(AnnouncementResource::getUrl('edit', ['record' => $central]))->assertNotFound();

        // An author with no department posts for the whole hospital, and sees them all.
        $this->actingAs(User::factory()->create());
        Livewire::test(ListAnnouncements::class)->assertCanSeeTableRecords([$own, $other, $central]);
    }

    public function test_what_it_posts_goes_on_its_own_board_and_takes_no_place_of_the_whole_hospitals(): void
    {
        // With approval switched off (tests/Feature/PostingFlowTest.php has it on): what it posts is out at once.
        config(['bchsp.security.department_approval' => false]);

        $this->actingAs($this->account);

        Livewire::test(CreateAnnouncement::class)
            ->assertFormFieldHidden('department_id')
            ->assertFormFieldHidden('is_pinned')
            ->assertFormFieldHidden('is_popup')
            ->assertFormFieldVisible('in_slider')
            ->fillForm(['title' => 'Dental clinic closed on Friday', 'announcement_type_id' => AnnouncementType::where('slug', 'news')->value('id')])
            ->call('create')
            ->assertHasNoFormErrors();

        $announcement = Announcement::firstOrFail();

        $this->assertSame($this->dental->id, $announcement->department_id);
        $this->assertSame($this->account->id, $announcement->author_id);
        $this->assertFalse($announcement->is_pinned);
        $this->assertFalse($announcement->is_popup);

        // On the department's page, and in the list of everything.
        auth()->logout();
        $this->get(route('departments.show', $this->dental))->assertSee('Dental clinic closed on Friday');
        $this->get('/announcements')->assertSee('Dental clinic closed on Friday');
    }

    public function test_it_cannot_hand_what_it_wrote_to_another_department(): void
    {
        $own = Announcement::factory()->create(['department_id' => $this->dental->id, 'announcement_type_id' => AnnouncementType::where('slug', 'news')->value('id')]);
        $this->actingAs($this->account);

        Livewire::test(EditAnnouncement::class, ['record' => $own->getRouteKey()])
            ->set('data.department_id', $this->pharmacy->id)
            ->fillForm(['title' => 'A new title'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('A new title', $own->fresh()->title);
        $this->assertSame($this->dental->id, $own->fresh()->department_id);
    }

    public function test_it_keeps_the_page_of_its_own_department_and_no_other(): void
    {
        $this->actingAs($this->account);

        Livewire::test(ManageDepartments::class)
            ->assertCanSeeTableRecords([$this->dental])
            ->assertCanNotSeeTableRecords([$this->pharmacy])
            ->assertActionHidden('create')
            ->assertActionHidden(TestAction::make(DeleteAction::class)->table($this->dental))
            ->callAction(TestAction::make(EditAction::class)->table($this->dental), ['description' => 'Fillings and extractions.', 'phone' => '044-579076 ต่อ 120', 'headline' => 'Care for teeth and gums', 'facebook_url' => 'https://www.facebook.com/dental'])
            ->assertHasNoFormErrors();

        $this->assertSame('Fillings and extractions.', $this->dental->fresh()->description);
        $this->assertSame('044-579076 ต่อ 120', $this->dental->fresh()->phone);
        $this->assertSame('Care for teeth and gums', $this->dental->fresh()->headline);
        $this->assertSame(['Facebook' => 'https://www.facebook.com/dental'], $this->dental->fresh()->links());
        // Its name and whether it is shown stay the administrator's.
        $this->assertSame('Dental', $this->dental->fresh()->name);
        $this->assertTrue($this->dental->fresh()->is_active);

        auth()->logout();
        $this->get(route('departments.show', $this->dental))->assertSee('Fillings and extractions.');
    }

    public function test_what_belongs_to_the_whole_hospital_is_out_of_its_reach(): void
    {
        $this->actingAs($this->account);

        foreach (['/admin/documents', '/admin/ita-items', '/admin/users', '/admin/site-settings', '/admin/announcement-types'] as $address) {
            $this->get($address)->assertForbidden();
        }

        foreach (['/admin', '/admin/announcements', '/admin/departments', '/admin/clinic-sessions'] as $address) {
            $this->get($address)->assertOk();
        }

        // An author with no department still keeps the documents, ITA and the schedule, but not the departments.
        $this->actingAs(User::factory()->create());

        foreach (['/admin/documents', '/admin/ita-items', '/admin/clinic-sessions'] as $address) {
            $this->get($address)->assertOk();
        }

        $this->get('/admin/departments')->assertForbidden();
    }

    public function test_it_keeps_its_own_clinic_schedule_and_only_that(): void
    {
        $own = ClinicSession::factory()->create(['name' => 'Dental clinic', 'department_id' => $this->dental->id]);
        $other = ClinicSession::factory()->create(['name' => 'Pharmacy counter', 'department_id' => $this->pharmacy->id]);
        $central = ClinicSession::factory()->create(['name' => 'Emergency room']);

        $this->actingAs($this->account);

        Livewire::test(ManageClinicSessions::class)
            ->assertCanSeeTableRecords([$own])
            ->assertCanNotSeeTableRecords([$other, $central])
            ->callAction('create', ['name' => 'Dentures', 'days' => [1, 2], 'starts_at' => '13:00', 'ends_at' => '16:00', 'department_id' => $this->pharmacy->id])
            ->assertHasNoActionErrors();

        $this->assertSame($this->dental->id, ClinicSession::where('name', 'Dentures')->value('department_id'));
    }

    public function test_it_keeps_the_people_of_its_own_groups_of_work_and_only_those(): void
    {
        $ownGroup = WorkGroup::factory()->create(['department_id' => $this->dental->id]);
        $otherGroup = WorkGroup::factory()->create(['department_id' => $this->pharmacy->id]);
        $own = Person::factory()->create(['name' => 'Dr Dent', 'work_group_id' => $ownGroup->id]);
        $other = Person::factory()->create(['name' => 'Pharmacist', 'work_group_id' => $otherGroup->id]);

        $this->actingAs($this->account);
        $this->get('/admin/people')->assertOk();

        Livewire::test(ManagePeople::class)
            ->assertCanSeeTableRecords([$own])
            ->assertCanNotSeeTableRecords([$other])
            ->callAction('create', ['name' => 'New nurse', 'work_group_id' => $otherGroup->id])
            ->assertHasActionErrors();

        $this->assertNull(Person::where('name', 'New nurse')->first());

        Livewire::test(ManagePeople::class)->callAction('create', ['name' => 'New nurse', 'work_group_id' => $ownGroup->id])->assertHasNoActionErrors();
        $this->assertSame($ownGroup->id, Person::where('name', 'New nurse')->value('work_group_id'));
    }

    public function test_an_administrator_gives_an_account_to_a_department(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageUsers::class)
            ->callAction('create', ['name' => 'Pharmacy desk', 'username' => 'pharmacy', 'role' => 'author', 'department_id' => $this->pharmacy->id, 'password' => 'a-long-password'])
            ->assertHasNoFormErrors();

        $made = User::where('username', 'pharmacy')->firstOrFail();

        $this->assertTrue($made->postsForOneDepartment());
        $this->assertTrue($made->department->is($this->pharmacy));

        // The department goes; the account stays, and posts for the whole hospital until it is given another.
        $this->pharmacy->delete();
        $this->assertFalse($made->fresh()->postsForOneDepartment());
    }

    public function test_the_screens_read_in_thai(): void
    {
        app()->setLocale('th');

        $this->actingAs($this->account)->get('/admin/departments')->assertSee('หน้าของหน่วยงานเรา');
        $this->actingAs(User::factory()->admin()->create())->get('/admin/users')->assertOk();
    }
}
