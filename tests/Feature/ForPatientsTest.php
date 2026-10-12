<?php

namespace Tests\Feature;

use App\Filament\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Resources\Faqs\Pages\ManageFaqs;
use App\Filament\Resources\StaffLinks\Pages\ManageStaffLinks;
use App\Models\Announcement;
use App\Models\AnnouncementType;
use App\Models\Department;
use App\Models\Faq;
use App\Models\StaffLink;
use App\Models\User;
use App\Support\Menus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

/** What a patient comes for: answers to common questions, what can be done online, and what happens on which day. */
class ForPatientsTest extends TestCase
{
    use RefreshDatabase;

    private function childrenOf(string $group): array
    {
        return array_column(collect(Menus::items('all'))->firstWhere('key', $group)['children'], 'label');
    }

    public function test_questions_open_onto_their_answers_under_their_headings(): void
    {
        Faq::factory()->create(['question' => 'What should I bring?', 'answer' => "Your ID card.\nYour appointment slip.", 'group' => 'Coming in', 'sort_order' => 2]);
        Faq::factory()->create(['question' => 'When can I visit a patient?', 'answer' => 'From noon to 8 pm.', 'group' => 'Coming in', 'sort_order' => 1]);
        Faq::factory()->create(['question' => 'Is there parking?', 'answer' => 'Yes, <b>free</b>.']);
        Faq::factory()->create(['question' => 'Hidden question?', 'is_active' => false]);

        $page = $this->get('/faq')
            ->assertOk()
            ->assertSeeInOrder(['Coming in', 'When can I visit a patient?', 'From noon to 8 pm.', 'What should I bring?', 'Your ID card.', 'Is there parking?'])
            ->assertDontSee('Hidden question?')
            // Typed markup is shown as typed, never run.
            ->assertDontSee('<b>free</b>', false)
            ->getContent();

        // Told to search engines as questions and answers.
        preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $page, $found);
        $told = json_decode($found[1], true);

        $this->assertSame('FAQPage', $told['@type']);
        $this->assertCount(3, $told['mainEntity']);

        $this->assertContains('Questions people ask', $this->childrenOf('about'));
    }

    public function test_with_no_questions_the_page_says_so_and_the_menu_does_not_list_it(): void
    {
        $this->get('/faq')->assertOk()->assertSee('Nothing has been added here yet.')->assertDontSee('application/ld+json', false);
        $this->get('/services')->assertOk()->assertSee('Nothing has been added here yet.');

        $this->assertNotContains('Questions people ask', $this->childrenOf('about'));
        $this->assertNotContains('Services online', $this->childrenOf('about'));
        $this->assertNotContains('Calendar', $this->childrenOf('news'));
    }

    public function test_links_for_the_public_are_the_services_online_and_those_for_staff_stay_on_the_staff_page(): void
    {
        StaffLink::factory()->create(['title' => 'Book a visit', 'audience' => 'public', 'note' => 'Mor Prom', 'group' => 'Appointments']);
        StaffLink::factory()->create(['title' => 'Repair requests', 'audience' => 'staff']);
        StaffLink::factory()->create(['title' => 'Not a web address', 'audience' => 'public', 'url' => 'javascript:alert(1)']);
        StaffLink::factory()->create(['title' => 'Switched off', 'audience' => 'public', 'is_active' => false]);

        $this->get('/services')
            ->assertOk()
            ->assertSeeInOrder(['Appointments', 'Book a visit', 'Mor Prom'])
            ->assertDontSee('Repair requests')
            ->assertDontSee('javascript:alert', false)
            ->assertDontSee('Switched off');

        $this->get('/staff')->assertSee('Repair requests')->assertDontSee('Book a visit');
        $this->assertContains('Services online', $this->childrenOf('about'));
    }

    public function test_staff_write_a_question_and_mark_a_link_as_for_the_public(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ManageFaqs::class)
            ->callAction('create', ['question' => 'Is there parking?', 'answer' => 'Yes, free.'])
            ->assertHasNoFormErrors();

        $this->assertSame('Yes, free.', Faq::firstOrFail()->answer);

        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ManageStaffLinks::class)
            ->callAction('create', ['title' => 'Book a visit', 'url' => 'https://example.org/book', 'audience' => 'public'])
            ->assertHasNoFormErrors();

        $this->assertSame(1, StaffLink::forThePublic()->count());

        // The account of a department keeps neither.
        $desk = User::factory()->create(['department_id' => Department::factory()->create()->id]);
        $this->actingAs($desk)->get('/admin/faqs')->assertForbidden();
    }

    public function test_the_calendar_lists_a_month_day_by_day(): void
    {
        $this->travelTo(Carbon::parse('2026-11-10 09:00', config('app.timezone')));

        $drive = Announcement::factory()->create(['title' => 'Blood drive', 'event_on' => '2026-11-18', 'event_until' => '2026-11-19']);
        Announcement::factory()->create(['title' => 'Mobile unit at Ban Charat', 'event_on' => '2026-11-05']);
        Announcement::factory()->create(['title' => 'Dental clinic closed', 'event_on' => '2026-11-18']);
        Announcement::factory()->create(['title' => 'Next month', 'event_on' => '2026-12-02']);
        Announcement::factory()->create(['title' => 'No day of its own']);
        Announcement::factory()->create(['title' => 'A draft', 'event_on' => '2026-11-20', 'status' => Announcement::STATUS_DRAFT]);

        $this->get('/calendar')
            ->assertOk()
            ->assertSeeInOrder(['November 2026', 'Mobile unit at Ban Charat', 'Blood drive', 'until 19 Nov', 'Dental clinic closed'])
            ->assertSee('href="'.route('announcements.show', $drive).'"', false)
            ->assertDontSee('Next month')
            ->assertDontSee('No day of its own')
            ->assertDontSee('A draft');

        $this->get('/calendar?month=2026-12')->assertSeeInOrder(['December 2026', 'Next month'])->assertSee('href="'.route('calendar').'"', false);
        $this->get('/calendar?month=2027-01')->assertOk()->assertSee('Nothing is on the calendar for this month.');
        // Nonsense is this month.
        $this->get('/calendar?month=2026-13')->assertSee('November 2026');
        $this->get('/calendar?month[]=1')->assertSee('November 2026');

        // The announcement says its day and leads to the calendar; the News menu lists the calendar.
        $this->get(route('announcements.show', $drive))->assertSeeInOrder(['Takes place on', 'Wednesday 18 November 2026', '19 November'])->assertSee('month=2026-11', false);
        $this->assertContains('Calendar', $this->childrenOf('news'));

        app()->setLocale('th');
        $this->get('/calendar')->assertSee('พฤศจิกายน 2569');
    }

    public function test_an_author_gives_an_announcement_the_day_it_happens(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateAnnouncement::class)
            ->fillForm(['title' => 'Blood drive', 'announcement_type_id' => AnnouncementType::where('slug', 'news')->value('id'), 'event_on' => '2026-11-18', 'event_until' => '2026-11-10'])
            ->call('create')
            ->assertHasFormErrors(['event_until']);

        Livewire::test(CreateAnnouncement::class)
            ->fillForm(['title' => 'Blood drive', 'announcement_type_id' => AnnouncementType::where('slug', 'news')->value('id'), 'event_on' => '2026-11-18'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('2026-11-18', Announcement::firstOrFail()->event_on->toDateString());
    }

    public function test_the_pages_are_in_the_sitemap_and_read_in_thai(): void
    {
        $xml = $this->get('/sitemap.xml')->getContent();

        foreach (['faq', 'services', 'calendar'] as $name) {
            $this->assertStringContainsString(route($name), $xml);
        }

        app()->setLocale('th');

        $this->get('/faq')->assertSee('คำถามที่พบบ่อย');
        $this->get('/services')->assertSee('บริการออนไลน์');
        $this->get('/calendar')->assertSee('ปฏิทินกิจกรรม');
    }
}
