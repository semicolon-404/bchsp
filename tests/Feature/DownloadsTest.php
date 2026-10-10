<?php

namespace Tests\Feature;

use App\Filament\Resources\Documents\Pages\ManageDocuments;
use App\Http\Middleware\SetLocale;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DownloadsTest extends TestCase
{
    use RefreshDatabase;

    public function test_documents_are_listed_under_their_headings_with_the_unsorted_ones_last(): void
    {
        Document::factory()->create(['title' => 'Leave regulations', 'group' => 'Regulations']);
        Document::factory()->create(['title' => 'Meeting request form', 'group' => 'Forms']);
        Document::factory()->create(['title' => 'Something else']);
        Document::factory()->create(['title' => 'Withdrawn', 'is_active' => false]);

        $this->get('/downloads')
            ->assertOk()
            ->assertSeeInOrder(['Forms', 'Meeting request form', 'Regulations', 'Leave regulations', 'Other documents', 'Something else'])
            ->assertDontSee('Withdrawn');
    }

    public function test_within_a_heading_they_are_in_staffs_order(): void
    {
        Document::factory()->create(['title' => 'Second', 'group' => 'Forms', 'sort_order' => 2]);
        Document::factory()->create(['title' => 'First', 'group' => 'Forms', 'sort_order' => 1]);

        $this->get('/downloads')->assertSeeInOrder(['First', 'Second']);
    }

    public function test_each_says_what_kind_of_file_it_is_and_how_big(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('documents/a.pdf', str_repeat('x', 3072));
        Document::factory()->create(['title' => 'Price list', 'file_path' => 'documents/a.pdf']);
        Document::factory()->create(['title' => 'Spreadsheet', 'file_path' => 'documents/b.xlsx']);

        $this->get('/downloads')
            ->assertSee('documents/a.pdf')
            ->assertSee('PDF · 3 KB')
            ->assertSee('Excel');
    }

    public function test_an_empty_page_says_so(): void
    {
        $this->get('/downloads')->assertOk()->assertSee('Nothing has been added here yet.');
    }

    public function test_a_reader_in_english_gets_the_english_name_where_there_is_one(): void
    {
        Document::factory()->create(['title' => 'ระเบียบการลา', 'title_en' => 'Leave regulations']);

        $this->withUnencryptedCookie(SetLocale::COOKIE, 'en')->get('/downloads')->assertSee('Leave regulations')->assertDontSee('ระเบียบการลา');
        $this->withUnencryptedCookie(SetLocale::COOKIE, 'th')->get('/downloads')->assertSee('ระเบียบการลา');
    }

    public function test_an_author_adds_a_document_and_it_goes_to_the_end(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());
        Document::factory()->create(['sort_order' => 3]);

        Livewire::test(ManageDocuments::class)
            ->callAction('create', [
                'title' => 'แบบแสดงความจำนงเข้าร่วมประชุม',
                'group' => 'แบบฟอร์ม',
                'file_path' => UploadedFile::fake()->create('แบบฟอร์มประชุม.pdf', 200, 'application/pdf'),
            ])
            ->assertHasNoFormErrors();

        $document = Document::where('group', 'แบบฟอร์ม')->firstOrFail();

        $this->assertSame(4, $document->sort_order);
        $this->assertSame('แบบฟอร์มประชุม.pdf', $document->file_name);
        Storage::disk('public')->assertExists($document->file_path);
        // Kept under a name that cannot be guessed from the upload.
        $this->assertStringNotContainsString('แบบฟอร์ม', $document->file_path);
        $this->get('/downloads')->assertSee('แบบแสดงความจำนงเข้าร่วมประชุม');
    }

    public function test_a_file_that_could_carry_a_program_is_refused(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        foreach ([
            ['macro.docm', 'application/vnd.ms-word.document.macroEnabled.12'],
            ['page.html', 'text/html'],
            ['setup.exe', 'application/x-msdownload'],
            ['old.doc', 'application/msword'],
        ] as [$name, $type]) {
            Livewire::test(ManageDocuments::class)
                ->callAction('create', ['title' => 'A file', 'file_path' => UploadedFile::fake()->create($name, 50, $type)])
                ->assertHasFormErrors(['file_path']);
        }

        $this->assertSame(0, Document::count());
    }

    public function test_replacing_or_deleting_a_document_removes_its_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('documents/old.pdf', 'x');
        Storage::disk('public')->put('documents/new.pdf', 'y');
        $document = Document::factory()->create(['file_path' => 'documents/old.pdf']);

        $document->update(['file_path' => 'documents/new.pdf']);
        Storage::disk('public')->assertMissing('documents/old.pdf');

        $document->delete();
        Storage::disk('public')->assertMissing('documents/new.pdf');
    }

    public function test_the_page_is_in_the_top_bar_and_the_screens_read_in_thai(): void
    {
        app()->setLocale('th');

        $this->get('/downloads')->assertSee('แบบฟอร์มและเอกสาร')->assertSee('href="'.route('downloads').'" aria-current="page"', false);
        $this->actingAs(User::factory()->create())->get('/admin/documents')->assertOk()->assertSee('ดาวน์โหลด');
    }
}
