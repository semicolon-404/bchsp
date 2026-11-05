<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\Festivals\FestivalResource;
use App\Filament\Resources\Krathongs\Pages\ManageKrathongs;
use App\Models\Department;
use App\Models\Festival;
use App\Models\Krathong;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Festivals dress the home page within their days; at Loy Krathong the departments float their krathongs. */
class FestivalTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_festival_shows_under_the_pulse_only_within_its_days_and_while_on(): void
    {
        $this->get('/')->assertSee('class="ambulance"', false)->assertDontSee('class="festival', false);

        $festival = Festival::create(['name' => 'Christmas', 'theme' => 'christmas', 'starts_on' => today(), 'ends_on' => today()->addDays(3)]);
        // The ambulance keeps driving along the pulse above it.
        $this->get('/')->assertSee('festival is-christmas', false)->assertSee('class="ambulance"', false);

        $festival->update(['is_active' => false]);
        $this->get('/')->assertDontSee('festival is-christmas', false);

        $festival->update(['is_active' => true, 'starts_on' => today()->addDay()]);
        $this->get('/')->assertDontSee('festival is-christmas', false);
    }

    public function test_a_department_floats_one_krathong_with_its_wish_and_it_shows_on_the_river(): void
    {
        $festival = Festival::create(['name' => 'Loy Krathong', 'theme' => 'loykrathong', 'starts_on' => today(), 'ends_on' => today()]);
        $dental = Department::factory()->create(['name' => 'Dental']);
        $this->actingAs(User::factory()->create(['role' => Role::Author, 'department_id' => $dental->id]));

        Livewire::test(ManageKrathongs::class)
            ->callAction(TestAction::make(CreateAction::class), ['festival_id' => $festival->id, 'style' => 'lotus', 'wish' => 'Healthy teeth for all'])
            ->assertHasNoActionErrors();
        $this->assertSame($dental->id, Krathong::sole()->department_id);

        // A second one is turned away: one to a department.
        Livewire::test(ManageKrathongs::class)
            ->callAction(TestAction::make(CreateAction::class), ['festival_id' => $festival->id, 'style' => 'bread', 'wish' => 'Again']);
        $this->assertSame(1, Krathong::count());

        $this->get('/')->assertSee('festival is-loykrathong', false)->assertSee('Healthy teeth for all')->assertSee('krathong is-lotus', false);

        // The festivals themselves are the administrators'.
        $this->assertFalse(FestivalResource::canAccess());
    }

    public function test_staff_signed_in_preview_a_festival_before_its_days_and_visitors_do_not(): void
    {
        $festival = Festival::create(['name' => 'Christmas', 'theme' => 'christmas', 'starts_on' => today()->addMonth(), 'ends_on' => today()->addMonth()]);

        $this->get('/?festival='.$festival->id)->assertDontSee('festival is-christmas', false);
        $this->actingAs(User::factory()->create(['role' => Role::Admin]))->get('/?festival='.$festival->id)->assertSee('festival is-christmas', false);
    }
}
