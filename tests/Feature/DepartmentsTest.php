<?php

namespace Tests\Feature;

use App\Filament\Resources\Announcements\Pages\EditAnnouncement;
use App\Filament\Resources\Departments\Pages\ManageDepartments;
use App\Http\Middleware\SetLocale;
use App\Models\Announcement;
use App\Models\AnnouncementType;
use App\Models\ClinicSession;
use App\Models\Department;
use App\Models\User;
use App\Support\SiteSettings;
use Database\Seeders\DepartmentSeeder;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
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

    public function test_a_departments_page_shows_what_it_posted_itself_newest_first(): void
    {
        $dental = Department::factory()->create(['name' => 'Dental']);
        $pharmacy = Department::factory()->create(['name' => 'Pharmacy']);

        Announcement::factory()->create(['title' => 'Brush twice a day', 'department_id' => $dental->id, 'image_path' => 'announcements/brush.webp', 'body' => '<p>Morning and <strong>night</strong>.</p>']);
        Announcement::factory()->create(['title' => 'Dental clinic closed on Friday', 'department_id' => $dental->id, 'starts_at' => now()->subDays(3)]);
        Announcement::factory()->create(['title' => 'Old dental notice', 'department_id' => $dental->id, 'ends_at' => now()->subHour()]);
        Announcement::factory()->scheduled()->create(['title' => 'Dental notice for next week', 'department_id' => $dental->id]);
        Announcement::factory()->create(['title' => 'Medicine by post', 'department_id' => $pharmacy->id]);
        Announcement::factory()->create(['title' => 'Notice of the whole hospital']);

        $this->get(route('departments.show', $dental))
            ->assertOk()
            ->assertSeeInOrder(['Posts', 'Brush twice a day', 'Morning and night.', 'Dental clinic closed on Friday'])
            ->assertSee('announcements/brush.webp')
            // How many it has put out in all, the ended one too.
            ->assertSeeInOrder(['3</span> posts'], false)
            ->assertDontSee('Old dental notice')
            ->assertDontSee('Dental notice for next week')
            ->assertDontSee('Medicine by post')
            ->assertDontSee('Notice of the whole hospital');
    }

    public function test_the_board_leads_to_everything_the_department_put_out_and_the_list_can_be_narrowed_to_it(): void
    {
        $dental = Department::factory()->create(['name' => 'Dental']);
        $pharmacy = Department::factory()->create(['name' => 'Pharmacy']);
        Department::factory()->create(['name' => 'Records']);

        Announcement::factory()->create(['title' => 'Brush twice a day', 'department_id' => $dental->id]);
        Announcement::factory()->ended()->create(['title' => 'Old dental notice', 'department_id' => $dental->id]);
        Announcement::factory()->create(['title' => 'Medicine by post', 'department_id' => $pharmacy->id]);
        Announcement::factory()->create(['title' => 'Notice of the whole hospital']);

        $all = route('announcements.index', ['department' => $dental->slug]);

        // The board says how many there are in all, the ended ones too.
        $this->get(route('departments.show', $dental))->assertSee('href="'.$all.'"', false)->assertSee('See all 2');

        $this->get($all)
            ->assertOk()
            ->assertSee('2 announcements')
            ->assertSee('Brush twice a day')
            ->assertSee('Old dental notice')
            ->assertDontSee('Medicine by post')
            ->assertDontSee('Notice of the whole hospital')
            // The choice is kept in the other choices of the page.
            ->assertSee('department='.$dental->slug.'&amp;view=cards', false);

        // Only departments that have put something out are offered; one that is not is nothing.
        $this->get('/announcements')->assertSee('Every department')->assertSee('Pharmacy')->assertDontSee('Records');
        $this->get('/announcements?department=no-such')->assertOk()->assertSee('4 announcements');
        $this->get('/announcements?department[]=1')->assertOk()->assertSee('4 announcements');
    }

    public function test_a_department_that_posted_nothing_says_so(): void
    {
        $this->get(route('departments.show', Department::factory()->create()))
            ->assertOk()
            ->assertSee('This department has not posted anything yet.')
            ->assertDontSee('See all');
    }

    public function test_a_department_has_a_profile_a_cover_a_round_picture_a_line_and_where_else_it_is_found(): void
    {
        $dental = Department::factory()->create([
            'name' => 'กลุ่มงานทันตกรรม', 'headline' => 'Care for teeth and gums', 'phone' => '044-579076 ต่อ 120',
            'image_path' => 'departments/cover.webp', 'avatar_path' => 'departments/avatars/tooth.webp',
            'facebook_url' => 'https://www.facebook.com/dental', 'line_url' => 'javascript:alert(1)',
        ]);
        $plain = Department::factory()->create(['name' => 'งานเวชระเบียน']);

        $this->get(route('departments.show', $dental))
            ->assertOk()
            ->assertSeeInOrder(['departments/cover.webp', 'departments/avatars/tooth.webp', '<h1', 'กลุ่มงานทันตกรรม', 'Care for teeth and gums', 'href="tel:044579076"', 'href="https://www.facebook.com/dental"'], false)
            // What is not a web address over HTTPS is never a link.
            ->assertDontSee('javascript:alert', false)
            // No LINE of its own (the share window's LINE button is another thing).
            ->assertDontSee('btn btn-line">LINE<', false);

        // Without pictures: a band of colour, and the first letter of its name past "งาน".
        $this->assertSame('ท', $dental->initial());
        $this->assertSame('ว', $plain->initial());
        $this->get(route('departments.show', $plain))->assertOk()->assertSee('>ว</span>', false)->assertDontSee('<img', false);

        // And in the list of departments.
        $this->get('/departments')->assertSee('departments/avatars/tooth.webp', false)->assertSee('Care for teeth and gums');
    }

    public function test_a_profile_lists_its_services_its_people_and_when_it_is_open(): void
    {
        // A Tuesday morning.
        $this->travelTo(Carbon::parse('2026-11-10 09:30', config('app.timezone')));

        $dental = Department::factory()->create([
            'name' => 'Dental', 'email' => 'dental@example.org', 'services' => "Fillings\n\n  Scaling  \nDentures",
            'people' => [
                ['name' => 'Dr Somchai', 'position' => 'Dentist', 'photo' => 'departments/people/somchai.webp'],
                ['name' => 'Malee', 'position' => null, 'photo' => null],
                ['name' => '  ', 'position' => 'Nobody'],
                'nonsense',
            ],
        ]);
        ClinicSession::factory()->create(['name' => 'Dental clinic', 'department_id' => $dental->id, 'days' => [1, 2, 3], 'starts_at' => '08:00', 'ends_at' => '12:00']);
        ClinicSession::factory()->create(['name' => 'Afternoon dentures', 'department_id' => $dental->id, 'days' => [0, 1, 2, 3, 4, 5, 6], 'starts_at' => '13:00', 'ends_at' => '16:00']);
        ClinicSession::factory()->create(['name' => 'Clinic of another']);

        $this->assertSame(['Fillings', 'Scaling', 'Dentures'], $dental->serviceList());
        $this->assertSame(['Dr Somchai', 'Malee'], array_column($dental->staff(), 'name'));

        $this->get(route('departments.show', $dental))
            ->assertOk()
            ->assertSeeInOrder(['Open now', '2</span> people', 'href="mailto:dental@example.org"', 'Services', 'Fillings', 'Scaling', 'Dentures', 'When it is open', 'Dental clinic', 'Mon Tue Wed', '08.00–12.00', 'Afternoon dentures', 'Every day'], false)
            ->assertDontSee('Clinic of another')
            // Who works there is on a tab of its own.
            ->assertDontSee('Dr Somchai');

        $this->get(route('departments.show', ['department' => $dental, 'tab' => 'people']))
            ->assertOk()
            ->assertSeeInOrder(['Our people', 'departments/people/somchai.webp', 'Dr Somchai', 'Dentist', 'Malee'], false)
            ->assertDontSee('Nobody');

        // In the evening it says so.
        $this->travelTo(Carbon::parse('2026-11-10 19:00', config('app.timezone')));
        $this->get(route('departments.show', $dental))->assertSee('Closed now')->assertDontSee('Open now');

        // One with no people has no such tab, and one with no schedule does not say whether it is open.
        $plain = Department::factory()->create();
        $this->get(route('departments.show', ['department' => $plain, 'tab' => 'people']))->assertOk()->assertDontSee('Our people')->assertDontSee('Closed now');
    }

    public function test_the_photograph_of_someone_taken_off_the_list_is_removed(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('departments/people/a.webp', 'x');
        Storage::disk('public')->put('departments/people/b.webp', 'x');

        $department = Department::factory()->create(['people' => [['name' => 'A', 'photo' => 'departments/people/a.webp'], ['name' => 'B', 'photo' => 'departments/people/b.webp']]]);

        $department->update(['people' => [['name' => 'B', 'photo' => 'departments/people/b.webp']]]);
        Storage::disk('public')->assertMissing('departments/people/a.webp');
        Storage::disk('public')->assertExists('departments/people/b.webp');

        $department->delete();
        Storage::disk('public')->assertMissing('departments/people/b.webp');
    }

    public function test_every_picture_a_department_posted_is_gathered_on_one_tab(): void
    {
        $dental = Department::factory()->create(['name' => 'Dental']);
        $day = Announcement::factory()->create(['title' => 'Dental health day', 'department_id' => $dental->id, 'image_path' => 'announcements/pictures/day.webp', 'gallery' => ['announcements/albums/a.webp', 'announcements/albums/b.webp']]);
        Announcement::factory()->create(['title' => 'Words only', 'department_id' => $dental->id]);
        Announcement::factory()->create(['title' => 'Of another', 'image_path' => 'announcements/pictures/other.webp']);
        Announcement::factory()->scheduled()->create(['department_id' => $dental->id, 'image_path' => 'announcements/pictures/later.webp']);

        $this->assertCount(3, $dental->photos());

        // The count on the tab; the album itself shows in its post as tiles that open the photo viewer.
        $this->get(route('departments.show', $dental))->assertSeeInOrder(['3</span> photographs'], false)->assertSee('data-lightbox', false);

        $this->get(route('departments.show', ['department' => $dental, 'tab' => 'photos']))
            ->assertOk()
            ->assertSeeInOrder(['pictures/day.webp', 'albums/a.webp', 'albums/b.webp'], false)
            ->assertSee('href="'.route('announcements.show', $day).'"', false)
            ->assertDontSee('other.webp', false)
            ->assertDontSee('later.webp', false)
            ->assertDontSee('Words only');

        $this->get(route('departments.show', ['department' => $dental, 'tab' => 'nonsense']))->assertOk()->assertSee('Words only');
        $this->get(route('departments.show', $dental).'?tab[]=1')->assertOk();
    }

    public function test_the_departments_pictures_go_when_they_are_replaced_and_when_it_does(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('departments/avatars/old.webp', 'x');
        Storage::disk('public')->put('departments/avatars/new.webp', 'x');
        Storage::disk('public')->put('departments/cover.webp', 'x');

        $department = Department::factory()->create(['avatar_path' => 'departments/avatars/old.webp', 'image_path' => 'departments/cover.webp']);

        $department->update(['avatar_path' => 'departments/avatars/new.webp']);
        Storage::disk('public')->assertMissing('departments/avatars/old.webp');

        $department->delete();
        Storage::disk('public')->assertMissing('departments/avatars/new.webp');
        Storage::disk('public')->assertMissing('departments/cover.webp');
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
