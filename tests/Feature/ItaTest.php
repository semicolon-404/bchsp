<?php

namespace Tests\Feature;

use App\Filament\Resources\ItaItems\Pages\CreateItaItem;
use App\Filament\Resources\ItaItems\Pages\ListItaItems;
use App\Models\ItaItem;
use App\Models\SitePage;
use App\Models\User;
use App\Support\Menus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ItaTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_page_says_so_while_there_is_nothing(): void
    {
        $this->get('/ita')->assertOk()->assertSee('Nothing has been added here yet.');
        $this->get('/ita/2569')->assertNotFound();
    }

    public function test_it_opens_on_the_newest_year_with_its_items_in_order(): void
    {
        ItaItem::factory()->create(['year' => 2568, 'number' => 1, 'code' => 'MOIT 1', 'title' => 'Last year structure']);
        ItaItem::factory()->create(['year' => 2569, 'number' => 10, 'code' => 'MOIT 10', 'title' => 'Ethics standard']);
        ItaItem::factory()->create(['year' => 2569, 'number' => 2, 'code' => 'MOIT 2', 'title' => 'Structure of the hospital']);
        ItaItem::factory()->create(['year' => 2569, 'number' => 3, 'title' => 'Hidden item', 'is_active' => false]);

        $this->get('/ita')
            ->assertOk()
            ->assertSee('<title>ITA 2569 · ', false)
            ->assertSeeInOrder(['Fiscal year 2569', 'MOIT 2', 'Structure of the hospital', 'MOIT 10', 'Ethics standard'])
            ->assertDontSee('Last year structure')
            ->assertDontSee('Hidden item');
    }

    public function test_an_earlier_year_has_its_own_address_and_the_years_are_offered_newest_first(): void
    {
        ItaItem::factory()->create(['year' => 2568, 'title' => 'Last year structure']);
        ItaItem::factory()->create(['year' => 2569, 'title' => 'This year structure']);

        $this->assertSame([2569, 2568], ItaItem::years());

        $this->get('/ita/2568')
            ->assertOk()
            ->assertSee('Last year structure')
            ->assertDontSee('This year structure')
            ->assertSee('href="'.route('ita').'"', false);

        $this->get('/ita/2567')->assertNotFound();
        $this->get('/ita/abc')->assertNotFound();
    }

    public function test_an_item_lists_its_files_and_its_links_and_says_how_many(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('ita/abc.pdf', str_repeat('x', 2048));
        ItaItem::factory()->create([
            'year' => 2569, 'number' => 1, 'title' => 'Procurement plan',
            'attachments' => ['ita/abc.pdf'], 'attachment_names' => ['ita/abc.pdf' => 'แผนจัดซื้อ 2569.pdf'],
            'links' => [['label' => 'รายงานผลไตรมาส 1', 'url' => 'https://drive.google.com/file/d/abc/view'], ['label' => 'Bad', 'url' => 'javascript:alert(1)']],
        ]);
        ItaItem::factory()->create(['year' => 2569, 'number' => 2, 'title' => 'Nothing yet']);

        $this->get('/ita')
            ->assertSee('แผนจัดซื้อ 2569.pdf')
            ->assertSee('PDF · 2 KB')
            ->assertSee('รายงานผลไตรมาส 1')
            ->assertSee('https://drive.google.com/file/d/abc/view', false)
            ->assertSee('drive.google.com')
            ->assertSee('2 documents')
            ->assertSee('No documents yet')
            ->assertDontSee('javascript:alert', false);
    }

    public function test_the_menu_opens_onto_the_years_and_the_pages_filed_under_ita(): void
    {
        $this->assertSame([], collect(Menus::items('top'))->firstWhere('key', 'ita')['children']);

        ItaItem::factory()->create(['year' => 2569]);
        ItaItem::factory()->create(['year' => 2568]);
        SitePage::factory()->create(['title' => 'Anti-corruption plan', 'section' => 'ita']);

        $ita = collect(Menus::items('top'))->firstWhere('key', 'ita');

        $this->assertSame(['Fiscal year 2569', 'Fiscal year 2568', 'Anti-corruption plan'], array_column($ita['children'], 'label'));
        $this->assertSame([route('ita'), route('ita', ['year' => 2568])], array_slice(array_column($ita['children'], 'url'), 0, 2));

        $this->get('/ita')->assertSee('Related pages')->assertSee('Anti-corruption plan');
        $this->get('/menu')->assertSeeInOrder(['ITA', 'Fiscal year 2569', 'Fiscal year 2568', 'Anti-corruption plan', 'News']);
    }

    public function test_a_new_year_is_started_from_the_headings_of_the_one_before(): void
    {
        Storage::fake('public');
        ItaItem::factory()->create(['year' => 2569, 'number' => 2, 'code' => 'MOIT 2', 'title' => 'Structure', 'attachments' => ['ita/a.pdf']]);
        ItaItem::factory()->create(['year' => 2569, 'number' => 1, 'code' => 'MOIT 1', 'title' => 'Plan', 'links' => [['label' => 'x', 'url' => 'https://example.com']]]);

        $this->assertSame(2, ItaItem::startYearFrom(2569, 2570));

        $new = ItaItem::where('year', 2570)->orderBy('number')->get();

        $this->assertSame(['MOIT 1', 'MOIT 2'], $new->pluck('code')->all());
        $this->assertSame(['Plan', 'Structure'], $new->pluck('title')->all());
        // The headings only: last year's evidence is not this year's.
        $this->assertNull($new[0]->links);
        $this->assertNull($new[1]->attachments);

        // Never over a year that has been started.
        $this->assertSame(0, ItaItem::startYearFrom(2569, 2570));
        $this->assertSame(0, ItaItem::startYearFrom(2569, 2569));
        $this->assertSame(2, ItaItem::where('year', 2570)->count());
    }

    public function test_an_author_starts_a_year_and_adds_evidence_from_the_panel(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());
        ItaItem::factory()->create(['year' => 2569, 'number' => 1, 'title' => 'Plan']);

        Livewire::test(ListItaItems::class)
            ->callAction('startYear', ['from' => 2569, 'to' => 2570])
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertSame(1, ItaItem::where('year', 2570)->count());

        Livewire::test(CreateItaItem::class)
            ->fillForm([
                'year' => 2570, 'number' => 2, 'title' => 'Structure of the hospital',
                'attachments' => [UploadedFile::fake()->create('โครงสร้าง.pdf', 200, 'application/pdf')],
                'links' => [['label' => 'คำสั่ง', 'url' => 'https://drive.google.com/file/d/abc/view']],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $item = ItaItem::where('year', 2570)->where('number', 2)->firstOrFail();

        $this->assertSame('MOIT 2', $item->code);
        $this->assertCount(2, $item->evidence());
        $this->assertSame('โครงสร้าง.pdf', $item->evidence()[0]['name']);

        $path = $item->attachments[0];
        $item->delete();
        Storage::disk('public')->assertMissing($path);
    }

    public function test_only_pdf_files_and_secure_links_are_taken(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateItaItem::class)
            ->fillForm([
                'year' => 2570, 'number' => 1, 'title' => 'Plan',
                'attachments' => [UploadedFile::fake()->create('macro.docm', 100, 'application/vnd.ms-word.document.macroEnabled.12')],
                'links' => [['label' => 'x', 'url' => 'http://example.com/a']],
            ])
            ->call('create')
            ->assertHasFormErrors();

        $this->assertSame(0, ItaItem::count());
    }

    public function test_the_screens_read_in_thai(): void
    {
        app()->setLocale('th');
        ItaItem::factory()->create(['year' => 2569, 'number' => 1, 'title' => 'แผนปฏิบัติการ']);

        $this->get('/ita')->assertSee('การประเมินคุณธรรมและความโปร่งใส (ITA)')->assertSee('ปีงบประมาณ 2569')->assertSee('ยังไม่มีเอกสาร');
        $this->actingAs(User::factory()->create())->get('/admin/ita-items')->assertOk()->assertSee('หัวข้อ ITA')->assertSee('เริ่มปีงบประมาณใหม่');
    }
}
