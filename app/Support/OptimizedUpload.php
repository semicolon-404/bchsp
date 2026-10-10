<?php

namespace App\Support;

use App\Services\ImageOptimizer;
use Filament\Forms\Components\FileUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A picture uploaded in the panel is made smaller and turned into WebP as it
 * is kept (App\Services\ImageOptimizer). Staff upload what their phone or
 * camera gave them; visitors on mobile data get a file a tenth of the size.
 *
 * On a panel form:  OptimizedUpload::on(FileUpload::make('cover_path')->directory('blog/covers'))
 */
class OptimizedUpload
{
    /** Make a picture field of a panel form keep its uploads this way. */
    public static function on(FileUpload $field): FileUpload
    {
        return $field->saveUploadedFileUsing(
            fn (UploadedFile $file, FileUpload $component) => self::store($file, $component->getDiskName(), (string) $component->getDirectory())
        );
    }

    /**
     * Keep the picture, as WebP when that is smaller, under a name nobody can guess.
     *
     * @return string its path on the disk
     */
    public static function store(UploadedFile $file, string $disk, string $directory): string
    {
        $webp = app(ImageOptimizer::class)->toWebp($file->getRealPath(), $file->getMimeType());

        if ($webp === null) {
            return $file->store($directory, ['disk' => $disk, 'visibility' => 'public']);
        }

        $path = trim($directory.'/'.Str::random(40).'.webp', '/');
        Storage::disk($disk)->put($path, $webp['data'], 'public');

        return $path;
    }
}
