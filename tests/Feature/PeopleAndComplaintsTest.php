<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Pages\SiteSettings as SiteSettingsPage;
use App\Filament\Resources\People\Pages\ManagePeople;
use App\Filament\Resources\WorkGroups\Pages\ManageWorkGroups;
use App\Models\Person;
use App\Models\User;
use App\Models\WorkGroup;
use App\Support\Organisation;
use App\Support\SiteSettings;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/** The register of the people the site names. (Complaints: ComplaintsTest.) */
class PeopleAndComplaintsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_person_is_kept_once_and_named_wherever_they_appear(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('people/boon.webp', 'x');

        $director = Person::factory()->create(['name' => 'Dr. Somchai Jaidee']);
        $boon = Person::factory()->create(['name' => 'Mr. Boon Kra', 'position' => 'Pharmacist', 'photo_path' => 'people/boon.webp']);
        SiteSettings::save(['org.director_person' => $director->id]);
        WorkGroup::factory()->create(['name' => 'Pharmacy', 'head_id' => $boon->id, 'head_name' => null, 'sort_order' => 1]);
        WorkGroup::factory()->create(['name' => 'Administration', 'head_id' => $boon->id, 'head_name' => null, 'sort_order' => 2]);
        // One written before there was a register still shows, by the name on the group.
        WorkGroup::factory()->create(['name' => 'Radiology', 'head_name' => 'Mrs. Pim Prasat', 'head_position' => 'Radiographer', 'sort_order' => 3]);

        $this->assertSame('Dr. Somchai Jaidee', Organisation::director()['name']);

        $heads = Organisation::heads();
        $this->assertSame(['Mr. Boon Kra', 'Mrs. Pim Prasat'], array_column($heads, 'name'));
        $this->assertCount(2, $heads[0]['groups']);
        $this->assertSame(Storage::disk('public')->url('people/boon.webp'), $heads[0]['photo']);

        // The one photograph shows on both pages.
        $this->get('/p/executives')->assertSee('people/boon.webp', false)->assertSee('Pharmacist')->assertSee('Radiographer');
        $this->get('/p/structure')->assertSee('people/boon.webp', false)->assertSee('Mrs. Pim Prasat');

        // A name corrected in the register is corrected everywhere.
        $boon->update(['name' => 'Mr. Boonnak Kra']);
        $this->get('/p/structure')->assertSee('Mr. Boonnak Kra')->assertDontSee('Mr. Boon Kra');

        // Taken out of the register, the groups are left without a head and not without themselves.
        $boon->delete();
        Storage::disk('public')->assertMissing('people/boon.webp');
        $this->assertNull(WorkGroup::where('name', 'Pharmacy')->first()->headName());
        $this->get('/p/structure')->assertOk()->assertSee('Pharmacy');
    }

    public function test_an_administrator_keeps_the_register_and_chooses_heads_and_the_director_from_it(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Author]));
        $this->get('/admin/people')->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
        $this->get('/admin/people')->assertOk();

        Livewire::test(ManagePeople::class)
            ->callAction('create', ['name' => 'Mrs. Malee Rakdee', 'position' => 'Registered nurse'])
            ->assertHasNoFormErrors();

        $malee = Person::sole();
        $this->assertSame(1, $malee->sort_order);

        Livewire::test(ManageWorkGroups::class)
            ->callAction('create', ['name' => 'Nursing', 'head_id' => $malee->id, 'staff_count' => 70, 'units' => ['Wards']])
            ->assertHasNoFormErrors();

        $this->assertTrue(WorkGroup::sole()->head->is($malee));
        $this->assertSame(1, $malee->heads()->count());

        Livewire::test(SiteSettingsPage::class)
            ->fillForm(['org__director_person' => $malee->id, 'org__director_title' => 'Acting director'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['name' => 'Mrs. Malee Rakdee', 'title' => 'Acting director', 'photo' => null, 'focus' => '50% 18%'], Organisation::director());
    }

    public function test_the_part_of_a_photograph_that_shows_is_chosen_and_is_near_the_top_until_then(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));
        // The photograph is really there: the form would drop one that is not.
        Storage::fake('public');
        Storage::disk('public')->put('people/boon.webp', 'x');
        $boon = Person::factory()->create(['name' => 'Mr. Boon Kra', 'photo_path' => 'people/boon.webp']);
        WorkGroup::factory()->create(['name' => 'Pharmacy', 'head_id' => $boon->id]);

        // A portrait shows its face near the top, not its middle.
        $this->assertSame('50% 18%', $boon->photoPosition());
        $this->get('/p/executives')->assertSee('style="object-position: 50% 18%"', false);

        // Dragged into place in the panel, it shows there on every page.
        Livewire::test(ManagePeople::class)
            ->callAction(TestAction::make('edit')->table($boon), ['photo_focus' => '40 62'])
            ->assertHasNoFormErrors();

        $this->assertSame('40% 62%', $boon->fresh()->photoPosition());
        $this->get('/p/executives')->assertSee('style="object-position: 40% 62%"', false);
        $this->get('/p/structure')->assertSee('style="object-position: 40% 62%"', false);

        // Nothing but two figures up to a hundred is ever written into a page.
        foreach (['50 180', 'red; x', '', '10'] as $odd) {
            $boon->update(['photo_focus' => $odd]);
            $this->assertSame('50% 18%', $boon->fresh()->photoPosition());
        }
    }
}
