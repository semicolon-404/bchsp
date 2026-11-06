<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

/**
 * Every file the announcements of one fiscal year carry, in one zip, to keep apart from the site (the panel's
 * announcements, "Download the files of a fiscal year"): a folder for each announcement, named by its day and title,
 * its attached files under the names they were uploaded with. Administrators only. Built on asking, into a temporary
 * file that is deleted once sent: nothing is kept, and nothing runs while nobody asks.
 */
class FilesArchiveController extends Controller
{
    public function __invoke(Request $request, int $year): BinaryFileResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        abort_unless($year >= 2500 && $year <= 2700, 404);

        $disk = Storage::disk('public');
        $path = tempnam(sys_get_temp_dir(), 'bchsp-files-');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);
        $added = 0;

        Announcement::withTrashed()->ofFiscalYear($year)->orderBy('starts_at')->orderBy('id')
            ->each(function (Announcement $announcement) use ($zip, $disk, &$added) {
                $folder = ($announcement->starts_at ?? $announcement->created_at)->format('Y-m-d').' '.Str::limit(Str::squish(preg_replace('~[\\\\/:*?"<>|]+~', ' ', $announcement->title)), 80, '');
                $names = (array) $announcement->attachment_names;

                foreach (array_filter((array) $announcement->attachments, 'is_string') as $file) {
                    if ($disk->exists($file)) {
                        $zip->addFile($disk->path($file), $folder.'/'.($names[$file] ?? basename($file)));
                        $added++;
                    }
                }
            });

        abort_if($added === 0, 404, __('No announcement of this fiscal year has files.'));
        $zip->close();

        return response()->download($path, __('Announcement files, fiscal year :year', ['year' => $year]).'.zip')->deleteFileAfterSend();
    }
}
