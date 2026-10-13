<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Pages\SystemStatus;
use App\Models\Announcement;
use App\Models\AnnouncementType;
use App\Models\Department;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** What the site draws by itself where no picture was given: covers, the first screen, a department's symbol. */
class DrawnWithoutPicturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_announcement_without_a_picture_gets_a_cover_of_its_kind_and_day(): void
    {
        $type = AnnouncementType::where('slug', 'procurement')->firstOrFail();
        Announcement::factory()->create(['title' => 'Without a picture', 'announcement_type_id' => $type->id, 'starts_at' => '2026-03-07 09:00']);
        Announcement::factory()->create(['title' => 'With a picture', 'image_path' => 'announcements/pictures/one.webp', 'starts_at' => '2026-03-06 09:00']);

        $this->travelTo('2026-03-20 10:00');

        $page = $this->get('/announcements?view=cards')->assertOk();
        $page->assertSee('class="cover', false)->assertSee('--tone: '.$type->colour(), false);
        // The one without a picture is drawn a cover with its day on it; the one with a picture has none.
        $this->assertSame(1, substr_count($page->getContent(), 'class="cover-day'));
        $page->assertSeeInOrder(['class="cover-day', '7', 'Mar']);
    }

    public function test_the_first_screen_has_a_drawing_until_the_hospital_gives_a_photograph(): void
    {
        $this->get('/')->assertSee('class="hero-art"', false)->assertDontSee('class="hero-photo"', false);
        $this->get('/about')->assertSee('class="hero-art"', false);

        SiteSettings::save(['site.hero_image' => 'site/hospital.webp']);

        $this->get('/')->assertSee('class="hero-photo"', false)->assertDontSee('class="hero-art"', false);
    }

    public function test_a_department_without_a_picture_stands_under_its_symbol_or_its_first_letter(): void
    {
        Department::factory()->create(['name' => 'Dental', 'icon' => 'face-smile']);
        Department::factory()->create(['name' => 'Pharmacy']);
        Department::factory()->create(['name' => 'Odd', 'icon' => 'no-such-symbol']);

        $page = $this->get('/departments')->assertOk()->getContent();

        // One symbol drawn (the smile's own path), and two first letters.
        $this->assertSame(1, substr_count($page, 'size-[55%]'));
        $this->assertStringContainsString('aria-hidden="true">P</span>', $page);
        $this->assertStringContainsString('aria-hidden="true">O</span>', $page);
        $this->assertNull(Department::where('name', 'Odd')->first()->icon());
    }

    public function test_system_status_says_how_it_stands_in_a_word_and_the_worst_first(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));

        Livewire::test(SystemStatus::class)
            ->assertSee('Something needs fixing')
            ->assertSee('panel-health-card is-problem', false)
            ->assertSeeInOrder(['panel-health-card is-problem', 'panel-health-card is-ok'], false);
    }
}
