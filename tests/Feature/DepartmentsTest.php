<?php

namespace Tests\Feature;

use App\Filament\Resources\Announcements\Pages\EditAnnouncement;
use App\Filament\Resources\Departments\Pages\ManageDepartments;
use App\Http\Middleware\SetLocale;
use App\Models\Announcement;
use App\Models\AnnouncementType;
use App\Models\Department;
use App\Models\User;
use App\Support\SiteSettings;
use Database\Seeders\DepartmentSeeder;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DepartmentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_departments_are_listed_in_the_administrators_order(): void
    {
        Department::factory()->create(['name' => 'Pharmacy', 'sort_order' => 2]);
        Department::factory()->create(['name' => 'Emergency', 'sort_order' => 1]);
        Department::factory()->create(['name' => 'Hidden unit', 'is_active' => false]);

        $this->get('/departments')
            ->assertOk()
            ->assertSeeInOrder(['Emergency', 'Pharmacy'])
            ->assertDontSee('Hidden unit');
    }

    public function test_a_department_shows_where_it_is_its_hours_and_a_number_that_can_be_dialled(): void
    {
        $department = Department::factory()->create([
            'name' => 'Dental', 'description' => "Fillings and extractions.\nBy appointment.",
            'location' => 'Building 2, first floor', 'phone' => '044-579076 ต่อ 120', 'hours' => 'Mon–Fri 08.15–15.30',
        ]);

        $this->get(route('departments.show', $department))
            ->assertOk()
            ->assertSee('<title>Dental · ', false)
            ->assertSee('Building 2, first floor')
            ->assertSee('Mon–Fri 08.15–15.30')
            ->assertSee('044-579076 ต่อ 120')
            ->assertSee('href="tel:044579076"', false)
            ->assertSee('Fillings and extractions.');
    }

    public function test_what_was_not_filled_in_is_left_out(): void
    {
        $department = Department::factory()->create(['name' => 'Records']);

        $this->assertNull($department->dialUrl());
        $this->get(route('departments.show', $department))->assertOk()->assertDontSee('Opening hours')->assertDontSee('tel:', false);
    }

    public function test_one_that_is_switched_off_is_seen_only_by_an_administrator(): void
    {
        $department = Department::factory()->create(['is_active' => false]);

        $this->get(route('departments.show', $department))->assertNotFound();
        $this->actingAs(User::factory()->create())->get(route('departments.show', $department))->assertNotFound();
        $this->actingAs(User::factory()->admin()->create())->get(route('departments.show', $department))->assertOk();
    }

    public function test_a_department_has_a_board_of_what_it_put_out_itself(): void
    {
        $dental = Department::factory()->create(['name' => 'Dental']);
        $pharmacy = Department::factory()->create(['name' => 'Pharmacy']);

        Announcement::factory()->create(['title' => 'Brush twice a day', 'department_id' => $dental->id, 'image_path' => 'announcements/brush.webp']);
        Announcement::factory()->create(['title' => 'Dental clinic closed on Friday', 'department_id' => $dental->id]);
        Announcement::factory()->create(['title' => 'Old dental notice', 'department_id' => $dental->id, 'ends_at' => now()->subHour()]);
        Announcement::factory()->scheduled()->create(['title' => 'Dental notice for next week', 'department_id' => $dental->id]);
        Announcement::factory()->create(['title' => 'Medicine by post', 'department_id' => $pharmacy->id]);
        Announcement::factory()->create(['title' => 'Notice of the whole hospital']);

        $this->get(route('departments.show', $dental))
            ->assertOk()
            ->assertSeeInOrder(['Posters and announcements', 'Brush twice a day', 'Dental clinic closed on Friday'])
            ->assertSee('announcements/brush.webp')
            ->assertDontSee('Old dental notice')
            ->assertDontSee('Dental notice for next week')
            ->assertDontSee('Medicine by post')
            ->assertDontSee('Notice of the whole hospital');
    }

    public function test_a_department_that_put_out_nothing_has_no_board(): void
    {
        $this->get(route('departments.show', Department::factory()->create()))
            ->assertOk()
            ->assertDontSee('Posters and announcements');
    }

    public function test_an_announcement_leads_back_to_its_department(): void
    {
        $dental = Department::factory()->create(['name' => 'Dental']);
        $hidden = Department::factory()->create(['name' => 'Hidden unit', 'is_active' => false]);

        $this->get(route('announcements.show', Announcement::factory()->create(['department_id' => $dental->id])))
            ->assertOk()->assertSee(route('departments.show', $dental));
        $this->get(route('announcements.show', Announcement::factory()->create(['department_id' => $hidden->id])))
            ->assertOk()->assertDontSee('Hidden unit');
    }

    public function test_what_a_deleted_department_put_out_is_kept(): void
    {
        $department = Department::factory()->create();
        $announcement = Announcement::factory()->create(['department_id' => $department->id]);

        $department->delete();

        $this->assertNull($announcement->fresh()->department_id);
    }

    public function test_staff_choose_the_department_of_an_announcement_in_the_panel(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $dental = Department::factory()->create(['name' => 'Dental']);
        $announcement = Announcement::factory()->create(['announcement_type_id' => AnnouncementType::factory()->create()->id]);

        Livewire::test(EditAnnouncement::class, ['record' => $announcement->getRouteKey()])
            ->fillForm(['department_id' => $dental->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($announcement->fresh()->department->is($dental));
    }

    public function test_nothing_typed_into_a_department_is_run_by_the_readers_browser(): void
    {
        $department = Department::factory()->create(['name' => 'Unit', 'description' => '<script>alert(1)</script>', 'hours' => '<img src=x onerror=alert(2)>']);

        $page = $this->get(route('departments.show', $department))->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert(1)', $page);
        $this->assertStringNotContainsString('<img src=x', $page);
    }

    public function test_the_home_page_shows_the_first_six_when_switched_on(): void
    {
        // Off at first; an administrator can put it on the home page (Site settings > Home page layout).
        SiteSettings::save(['home.layout' => [['key' => 'departments', 'shown' => true]]]);
        $this->get('/')->assertDontSee('All departments');

        foreach (range(1, 7) as $order) {
            Department::factory()->create(['name' => "Unit number $order", 'sort_order' => $order]);
        }

        $this->get('/')->assertSee('Unit number 6')->assertDontSee('Unit number 7')->assertSee('All departments');
    }

    public function test_a_reader_in_english_gets_the_english_name_where_there_is_one(): void
    {
        $department = Department::factory()->create(['name' => 'กลุ่มงานเภสัชกรรม', 'name_en' => 'Pharmacy']);

        $this->withUnencryptedCookie(SetLocale::COOKIE, 'en')->get('/departments')->assertSee('Pharmacy')->assertDontSee('กลุ่มงานเภสัชกรรม');
        $this->withUnencryptedCookie(SetLocale::COOKIE, 'th')->get('/departments')->assertSee('กลุ่มงานเภสัชกรรม');
        $this->assertSame('กลุ่มงานเภสัชกรรม', $department->slug);
    }

    public function test_an_administrator_adds_one_with_a_picture_and_it_goes_to_the_end(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->admin()->create());
        Department::factory()->create(['sort_order' => 4]);

        Livewire::test(ManageDepartments::class)
            ->callAction('create', ['name' => 'Dental', 'phone' => '044-579076', 'image_path' => UploadedFile::fake()->image('room.jpg', 2000, 1200)])
            ->assertHasNoFormErrors();

        $department = Department::where('name', 'Dental')->firstOrFail();

        $this->assertSame(5, $department->sort_order);
        $this->assertStringEndsWith('.webp', $department->image_path);
        Storage::disk('public')->assertExists($department->image_path);

        $department->delete();
        Storage::disk('public')->assertMissing($department->image_path);
    }

    public function test_an_administrator_hides_one_from_the_site(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $department = Department::factory()->create(['name' => 'Closed unit']);

        Livewire::test(ManageDepartments::class)
            ->callAction(TestAction::make(EditAction::class)->table($department), ['is_active' => false])
            ->assertHasNoFormErrors();

        auth()->logout();
        $this->get('/departments')->assertDontSee('Closed unit');
    }

    public function test_the_starting_list_is_added_once_and_never_over_what_staff_changed(): void
    {
        $this->seed(DepartmentSeeder::class);

        $this->assertSame(12, Department::count());
        $this->assertSame('กลุ่มงานบริหารทั่วไป', Department::shown()->first()->name);

        Department::query()->where('sort_order', '>', 1)->delete();
        $this->seed(DepartmentSeeder::class);

        $this->assertSame(1, Department::count());
    }

    public function test_only_an_administrator_manages_departments_and_the_screen_reads_in_thai(): void
    {
        app()->setLocale('th');

        $this->actingAs(User::factory()->create())->get('/admin/departments')->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get('/admin/departments')->assertOk()->assertSee('หน่วยงาน')->assertSee('เวลาทำการ');
    }
}
