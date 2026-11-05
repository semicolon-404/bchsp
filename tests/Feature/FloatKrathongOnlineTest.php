<?php

namespace Tests\Feature;

use App\Models\Festival;
use App\Models\Krathong;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

/** Visitors float krathongs at /loykrathong under a name of their choosing: at once, kind words only, a few each. */
class FloatKrathongOnlineTest extends TestCase
{
    use RefreshDatabase;

    private function festival(): Festival
    {
        return Festival::create(['name' => 'Loy Krathong', 'theme' => 'loykrathong', 'starts_on' => today(), 'ends_on' => today()]);
    }

    public function test_there_is_no_river_outside_loy_krathong(): void
    {
        $this->get('/loykrathong')->assertNotFound();

        Festival::create(['name' => 'Christmas', 'theme' => 'christmas', 'starts_on' => today(), 'ends_on' => today()]);
        $this->get('/loykrathong')->assertNotFound();
    }

    public function test_a_visitor_floats_a_krathong_under_their_name_and_it_is_on_the_river_at_once(): void
    {
        $this->festival();

        $this->post('/loykrathong', ['name' => 'Somchai', 'wish' => 'Good health to my mother', 'style' => 'lotus'])
            ->assertRedirect('/loykrathong');

        $this->assertSame('Somchai', Krathong::sole()->name);
        $this->get('/loykrathong')->assertSee('Good health to my mother')->assertSee('is-mine', false);
        $this->get('/')->assertSee('Good health to my mother');
    }

    public function test_rude_words_links_robots_and_a_fourth_are_turned_away(): void
    {
        // The route's throttle is a second guard; here the first ones are what is tried.
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->festival();

        $this->post('/loykrathong', ['name' => 'X', 'wish' => 'ไอ้ สัตว์', 'style' => 'banana'])->assertSessionHasErrors('wish');
        $this->post('/loykrathong', ['name' => 'X', 'wish' => 'visit spam.com now', 'style' => 'banana'])->assertSessionHasErrors('wish');
        $this->post('/loykrathong', ['name' => 'X', 'wish' => 'Hi', 'style' => 'banana', 'website' => 'http://bot']);
        $this->assertSame(0, Krathong::count());

        // An ordinary word that holds a rude one inside it is let through.
        $this->post('/loykrathong', ['name' => 'กู้ภัยบัวเชด', 'wish' => 'ขอให้ทุกคนปลอดภัย', 'style' => 'banana'])->assertSessionHasNoErrors();

        $this->post('/loykrathong', ['name' => 'A', 'wish' => 'Two', 'style' => 'banana']);
        $this->post('/loykrathong', ['name' => 'A', 'wish' => 'Three', 'style' => 'banana']);
        $this->post('/loykrathong', ['name' => 'A', 'wish' => 'Four', 'style' => 'banana'])->assertSessionHasErrors('wish');
        $this->assertSame(3, Krathong::count());
    }

    public function test_one_taken_off_the_river_is_not_shown(): void
    {
        $festival = $this->festival();
        $festival->krathongs()->create(['name' => 'Someone', 'wish' => 'Not for the river', 'style' => 'bread', 'is_hidden' => true]);

        $this->get('/loykrathong')->assertDontSee('Not for the river');
        $this->get('/')->assertDontSee('Not for the river');
    }
}
