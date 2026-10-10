<?php

namespace Tests\Feature;

use App\Filament\Resources\ClinicSessions\Pages\ManageClinicSessions;
use App\Http\Middleware\SetLocale;
use App\Models\ClinicSession;
use App\Models\Department;
use App\Models\User;
use App\Support\SiteSettings;
use Database\Seeders\ClinicSessionSeeder;
use Database\Seeders\DepartmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class ClinicScheduleTest extends TestCase
{
    use RefreshDatabase;

    /** A Tuesday morning in Thailand. */
    private function onTuesdayAt(string $time): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-13 '.$time, 'Asia/Bangkok'));
    }

    public function test_the_week_is_listed_monday_first_with_each_days_clinics_earliest_first(): void
    {
        $this->onTuesdayAt('09:00');
        ClinicSession::factory()->create(['name' => 'Afternoon dental', 'days' => [1], 'starts_at' => '13:00', 'ends_at' => '15:30']);
        ClinicSession::factory()->create(['name' => 'Morning diabetes', 'days' => [1, 2], 'starts_at' => '08:00', 'ends_at' => '12:00']);
        ClinicSession::factory()->create(['name' => 'Sunday clinic', 'days' => [0], 'starts_at' => '08:00', 'ends_at' => '12:00']);
        ClinicSession::factory()->create(['name' => 'Hidden clinic', 'days' => [1], 'is_active' => false]);

        $this->get('/schedule')
            ->assertOk()
            ->assertSeeInOrder(['Monday', '08.00–12.00', 'Morning diabetes', '13.00–15.30', 'Afternoon dental', 'Tuesday', 'Today', 'Morning diabetes', 'Wednesday', 'No clinics on this day.', 'Saturday', 'Sunday', 'Sunday clinic'])
            ->assertDontSee('Hidden clinic');
    }

    public function test_a_service_open_day_and_night_says_so_and_a_note_is_shown(): void
    {
        ClinicSession::factory()->create(['name' => 'Emergency', 'days' => [0, 1, 2, 3, 4, 5, 6], 'starts_at' => '00:00', 'ends_at' => '23:59', 'note' => 'Call 1669']);

        $this->get('/schedule')->assertSee('24 hours')->assertSee('Call 1669')->assertDontSee('00.00–23.59');
    }

    public function test_the_home_page_shows_what_is_open_today_and_what_is_open_now(): void
    {
        $this->onTuesdayAt('09:00');
        SiteSettings::save(['contact.phone' => '044-579076']);
        ClinicSession::factory()->create(['name' => 'Morning diabetes', 'days' => [2], 'starts_at' => '08:00', 'ends_at' => '12:00']);
        ClinicSession::factory()->create(['name' => 'Afternoon dental', 'days' => [2], 'starts_at' => '13:00', 'ends_at' => '15:30']);
        ClinicSession::factory()->create(['name' => 'Wednesday only', 'days' => [3]]);

        // Off until an administrator switches it on: the home page is kept open.
        $this->get('/')->assertOk()->assertDontSee('Open today');
        $this->showOnHome('today');

        $page = $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Open today', 'Tuesday', 'Morning diabetes', 'Open now', 'Afternoon dental', 'The whole week'])
            ->assertDontSee('Wednesday only')
            ->getContent();

        $this->assertSame(1, substr_count($page, 'Open now'));

        // Between the morning's clinic and the afternoon's, nothing is marked as open.
        $this->onTuesdayAt('12:00');
        $this->get('/')->assertSee('Open today')->assertDontSee('Open now');
    }

    public function test_a_clinic_is_open_from_its_first_minute_to_just_before_its_last(): void
    {
        $session = ClinicSession::factory()->create(['days' => [2], 'starts_at' => '08:00', 'ends_at' => '12:00']);

        foreach (['07:59' => false, '08:00' => true, '11:59' => true, '12:00' => false] as $time => $open) {
            $this->onTuesdayAt($time);
            $this->assertSame($open, $session->isOpenNow(), $time);
        }

        // Not on a day it is not held.
        Carbon::setTestNow(Carbon::parse('2026-10-14 09:00', 'Asia/Bangkok'));
        $this->assertFalse($session->isOpenNow());
    }

    public function test_until_there_is_a_schedule_the_home_page_says_nothing_of_today(): void
    {
        $this->showOnHome('today');

        $this->get('/')->assertOk()->assertDontSee('Open today');
        $this->get('/schedule')->assertOk()->assertSee('Nothing has been added here yet.');
    }

    public function test_a_clinic_links_to_its_department_when_that_is_shown(): void
    {
        $shown = Department::factory()->create(['name' => 'Dental unit']);
        $hidden = Department::factory()->create(['name' => 'Closed unit', 'is_active' => false]);
        ClinicSession::factory()->create(['department_id' => $shown->id]);
        ClinicSession::factory()->create(['department_id' => $hidden->id]);

        $this->get('/schedule')->assertSee(route('departments.show', $shown), false)->assertDontSee('Closed unit');
    }

    public function test_a_reader_in_english_gets_english_names_and_days(): void
    {
        ClinicSession::factory()->create(['name' => 'คลินิกเบาหวาน', 'name_en' => 'Diabetes clinic', 'days' => [1]]);

        $this->withUnencryptedCookie(SetLocale::COOKIE, 'en')->get('/schedule')->assertSee('Diabetes clinic')->assertSee('Monday');
        $this->withUnencryptedCookie(SetLocale::COOKIE, 'th')->get('/schedule')->assertSee('คลินิกเบาหวาน')->assertSee('วันจันทร์')->assertSee('ตารางออกตรวจ');
    }

    public function test_the_schedule_is_listed_under_about_us(): void
    {
        $this->get('/about')->assertSeeInOrder(['Clinic schedule', 'Departments', 'Contact us'])->assertSee('href="'.route('schedule').'"', false);
        $this->get('/menu')->assertSee('Clinic schedule');
    }

    public function test_staff_add_a_clinic_and_the_times_must_be_real(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ManageClinicSessions::class)
            ->callAction('create', ['name' => 'Diabetes clinic', 'days' => [], 'starts_at' => '8am', 'ends_at' => '25:00'])
            ->assertHasFormErrors(['days', 'starts_at', 'ends_at']);

        Livewire::test(ManageClinicSessions::class)
            ->callAction('create', ['name' => 'Diabetes clinic', 'days' => ['2', '4'], 'starts_at' => '08:00', 'ends_at' => '12:00', 'note' => 'By appointment'])
            ->assertHasNoFormErrors();

        $session = ClinicSession::firstOrFail();

        $this->assertTrue($session->isHeldOn(2));
        $this->assertTrue($session->isHeldOn(4));
        $this->assertFalse($session->isHeldOn(3));
        $this->assertSame('08.00–12.00', $session->hours());
    }

    public function test_the_starting_schedule_is_added_once_and_tied_to_the_departments(): void
    {
        $this->seed([DepartmentSeeder::class, ClinicSessionSeeder::class]);
        $count = ClinicSession::count();

        $this->assertGreaterThan(4, $count);
        $this->assertNotNull(ClinicSession::where('name', 'ตรวจโรคทั่วไป')->firstOrFail()->department);

        ClinicSession::query()->limit(2)->delete();
        $this->seed(ClinicSessionSeeder::class);

        $this->assertSame($count - 2, ClinicSession::count());
    }

    public function test_the_screen_reads_in_thai(): void
    {
        app()->setLocale('th');

        $this->actingAs(User::factory()->create())->get('/admin/clinic-sessions')->assertOk()->assertSee('ตารางออกตรวจ');
    }
}
