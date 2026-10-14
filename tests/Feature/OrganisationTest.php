<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Pages\SiteSettings as SiteSettingsPage;
use App\Filament\Resources\WorkGroups\Pages\ManageWorkGroups;
use App\Models\Department;
use App\Models\SitePage;
use App\Models\User;
use App\Models\WorkGroup;
use App\Support\Organisation;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** How the hospital is organised and who runs it: the two pages under About us that are drawn from the panel. */
class OrganisationTest extends TestCase
{
    use RefreshDatabase;

    private function anOrganisation(): void
    {
        SiteSettings::save([
            'org.director_name' => 'Dr. Somchai Jaidee', 'org.founded_on' => '1985-02-02',
            'org.mission_group' => 'Mission group of the hospital', 'org.doctors' => 9,
        ]);

        $dental = Department::factory()->create(['name' => 'Dental clinic']);
        WorkGroup::factory()->create(['name' => 'Nursing', 'head_name' => 'Mrs. Malee Rakdee', 'head_position' => 'Registered nurse', 'staff_count' => 70, 'units' => ['Outpatients', 'Wards', ''], 'sort_order' => 1]);
        WorkGroup::factory()->create(['name' => 'Pharmacy', 'head_name' => 'Mr. Boon Kra', 'head_position' => 'Pharmacist', 'staff_count' => 13, 'units' => ['Dispensing'], 'sort_order' => 2]);
        WorkGroup::factory()->create(['name' => 'Dental', 'head_name' => 'Dr. Pichet Sida', 'head_position' => 'Dentist', 'staff_count' => 12, 'units' => ['Dentistry'], 'department_id' => $dental->id, 'sort_order' => 3]);
        WorkGroup::factory()->create(['name' => 'Administration', 'head_name' => 'Mr.  Boon Kra', 'head_position' => null, 'staff_count' => 39, 'units' => ['Finance', 'Supplies'], 'sort_order' => 4]);
        WorkGroup::factory()->create(['name' => 'Hidden group', 'head_name' => 'Nobody Shown', 'is_active' => false]);
    }

    public function test_the_two_pages_are_pages_of_about_us_in_their_place(): void
    {
        $about = SitePage::published()->where('section', 'about')->orderBy('position')->orderBy('id')->pluck('slug')->all();

        $this->assertContains('structure', $about);
        $this->assertSame(array_search('structure', $about, true) + 1, array_search('executives', $about, true));

        // With nothing entered each says so, and is still a page.
        $this->get('/p/structure')->assertOk()->assertSee('The organisation chart has not been entered yet.');
        $this->get('/p/executives')->assertOk()->assertSee('The executives have not been entered yet.');
    }

    public function test_the_structure_is_drawn_from_the_director_down_to_the_units_of_work(): void
    {
        $this->anOrganisation();

        $this->get('/p/structure')
            ->assertOk()
            ->assertSeeInOrder(['Dr. Somchai Jaidee', 'Mission group of the hospital', '4 groups of work', '134 people', 'Nursing', 'Mrs. Malee Rakdee', 'Outpatients', 'Wards', 'Pharmacy', 'Dental', 'Administration', 'Finance'])
            // The share of the largest: the largest is the whole bar.
            ->assertSee('style="width: 100%"', false)
            // A group with a page on the site leads to it.
            ->assertSee('href="'.route('departments.show', Department::first()).'"', false)
            ->assertDontSee('Hidden group')
            // It leads on to the page after it, as a page of text does.
            ->assertSee('rel="next"', false);

        $this->assertSame(['groups' => 4, 'people' => 134, 'doctors' => 9], Organisation::figures());

        // The number an administrator gives for the whole hospital counts those outside any group too.
        SiteSettings::save(['org.staff_total' => 191]);
        $this->assertSame(191, Organisation::figures()['people']);
    }

    public function test_the_executives_are_the_director_and_each_head_once(): void
    {
        $this->anOrganisation();

        $heads = Organisation::heads();

        $this->assertSame(['Mrs. Malee Rakdee', 'Mr. Boon Kra', 'Dr. Pichet Sida'], array_column($heads, 'name'));
        // Who heads two groups is one entry, with both.
        $this->assertSame(['Pharmacy', 'Administration'], array_map(fn (WorkGroup $group) => $group->name, $heads[1]['groups']));
        $this->assertSame('Pharmacist', $heads[1]['position']);

        $page = $this->get('/p/executives')->assertOk();
        $page->assertSeeInOrder(['Head of the hospital', 'Somchai', 'Director of', 'Head of Mission group of the hospital', 'Heads of the groups of work', 'Malee', 'Registered nurse', 'Head of Nursing', 'Head of Pharmacy', 'Head of Administration', 'Head of Dental'])
            ->assertDontSee('Nobody Shown');
        $this->assertSame(1, substr_count($page->getContent(), '>Pharmacist<'));
    }

    public function test_a_person_without_a_photograph_stands_under_the_first_letter_of_the_name_itself(): void
    {
        $this->assertSame('S', Organisation::initial('Dr. Somchai Jaidee'));
        $this->assertSame('ว', Organisation::initial('นายวิชญนนท์ เหล่าอุดม'));
        $this->assertSame('ย', Organisation::initial('นางเยาวรีย์ อุปลิขิต'));
        $this->assertSame('อ', Organisation::initial('นางสาวอัฉราพรรณ ฤทธิ์ไธสง'));
        $this->assertSame('พ', Organisation::initial('ท.พ.พิเชษฐ สีดาหอม'));
        // The same person under two titles.
        $this->assertSame(Organisation::bareName('น.พ.วิชญนนท์ เหล่าอุดม'), Organisation::bareName('นายวิชญนนท์  เหล่าอุดม'));
    }

    public function test_about_us_says_since_when_and_who_leads_it(): void
    {
        $this->get('/about')->assertOk()->assertDontSee('The people behind the hospital');

        $this->anOrganisation();
        $this->travelTo('2026-10-14 10:00');

        $this->get('/about')
            ->assertSeeInOrder(['The people behind the hospital', 'Founded on 2 February 1985', 'at work for 41 years', 'Dr. Somchai Jaidee'])
            ->assertSee('href="'.route('pages.show', 'executives').'"', false)
            ->assertSee('href="'.route('pages.show', 'structure').'"', false);

        $this->assertSame(41, Organisation::years());
    }

    public function test_an_administrator_keeps_the_groups_and_the_director_and_nobody_else_does(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Author]));
        $this->get('/admin/work-groups')->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
        $this->get('/admin/work-groups')->assertOk();

        Livewire::test(ManageWorkGroups::class)
            ->callAction('create', ['name' => 'Radiology', 'staff_count' => 2, 'units' => ['X-ray']])
            ->assertHasNoFormErrors();

        $group = WorkGroup::sole();
        $this->assertSame(['X-ray'], $group->unitList());
        $this->assertSame(1, $group->sort_order);

        // Who heads it and who the director is are chosen from the register of people (PeopleAndComplaintsTest).
        Livewire::test(SiteSettingsPage::class)
            ->fillForm(['org__founded_on' => '1985-02-02', 'org__doctors' => 9, 'org__mission_group' => 'Mission group'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('1985-02-02', Organisation::foundedOn()->toDateString());
        $this->assertSame('Mission group', Organisation::missionGroup());
        $this->get('/p/structure')->assertSee('Radiology')->assertSee('Mission group');
    }
}
