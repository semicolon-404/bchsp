<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Announcement;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

/** Every file of a fiscal year's announcements in one zip, under the names they were uploaded with: administrators only. */
class FilesArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_downloads_a_fiscal_years_files_in_one_zip(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('announcements/files/a1.pdf', 'tender');
        Storage::disk('public')->put('announcements/files/b2.pdf', 'other year');
        Announcement::factory()->create(['title' => 'Tender: dental chairs', 'starts_at' => '2026-11-01 09:00', 'attachments' => ['announcements/files/a1.pdf'], 'attachment_names' => ['announcements/files/a1.pdf' => 'TOR.pdf']]);
        Announcement::factory()->create(['starts_at' => '2026-03-01 09:00', 'attachments' => ['announcements/files/b2.pdf']]);

        $response = $this->actingAs(User::factory()->create(['role' => Role::Admin]))->get('/admin/files-archive/2570');
        $response->assertOk();

        $zip = new ZipArchive;
        $zip->open($response->baseResponse->getFile()->getPathname());
        $this->assertSame(1, $zip->numFiles);
        $this->assertSame('2026-11-01 Tender dental chairs/TOR.pdf', $zip->getNameIndex(0));
        $this->assertSame('tender', $zip->getFromIndex(0));
        $zip->close();

        // A year with no files says so.
        $this->get('/admin/files-archive/2560')->assertNotFound();
    }

    public function test_a_department_or_a_visitor_cannot(): void
    {
        $this->get('/admin/files-archive/2570')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => Role::Author, 'department_id' => Department::factory()->create()->id]))
            ->get('/admin/files-archive/2570')->assertForbidden();
    }
}
