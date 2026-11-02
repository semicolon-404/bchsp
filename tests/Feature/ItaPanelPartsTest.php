<?php

namespace Tests\Feature;

use App\Filament\Resources\ItaItems\Pages\EditItaItem;
use App\Models\ItaItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** The panel's form for an ITA item: its heading and its parts, each with a round and links of its own. */
class ItaPanelPartsTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_give_a_part_a_link_from_the_items_form(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $item = ItaItem::factory()->create(['year' => 2569, 'number' => 4, 'parts' => [['round' => 'q2', 'title' => '2.1 Report']]]);

        $form = Livewire::test(EditItaItem::class, ['record' => $item->getRouteKey()])->assertOk();
        $key = array_key_first($form->get('data.parts'));

        $form
            ->set("data.parts.$key.links", [['label' => 'Report Q1-Q2', 'url' => 'https://drive.google.com/file/d/q/view']])
            ->set('data.category', '2. Procurement')
            ->call('save')
            ->assertHasNoFormErrors();

        $item->refresh();
        $this->assertSame('2. Procurement', $item->category);
        $this->assertSame(['done' => 1, 'of' => 1], $item->progress());
    }
}
