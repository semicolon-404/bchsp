<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Pictures kept before uploads were made smaller (App\Services\ImageOptimizer), or brought over from the old site,
 * can be thousands of dots wide: a logo 6000 wide is fetched by every page to be shown 36 high. These are made
 * smaller where they lie, under the same name and in the same format, so nothing that points at them changes.
 * Each original is kept first (ORIGINALS, on the private disk), so it can be put back.
 *
 * A few at a time (OldPictures::shrink(20)): a large site catches up over several runs without one doing it all.
 */
class OldPictures
{
    /** Longer than this on its longer side, a picture is made smaller to it. */
    private const MAX_SIDE = 1920;

    /** Smaller than this, a picture is left as it is. */
    private const LEAVE_UNDER_BYTES = 300 * 1024;

    /** Where each original is kept before its smaller copy takes its place (on the private disk). */
    public const ORIGINALS = 'picture-originals';

    /** A picture this large in dots would need more memory than a small host gives. */
    private const MAX_PIXELS = 40_000_000;

    /** @return int how many were made smaller */
    public static function shrink(int $atMost = 20): int
    {
        if (! function_exists('imagecreatefromstring')) {
            return 0;
        }

        $disk = Storage::disk('public');
        $done = 0;

        foreach ($disk->allFiles() as $path) {
            if ($done >= $atMost) {
                break;
            }

            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) || $disk->size($path) < self::LEAVE_UNDER_BYTES) {
                continue;
            }

            if (self::shrinkOne($disk->path($path), $extension, $path)) {
                $done++;
            }
        }

        return $done;
    }

    private static function shrinkOne(string $file, string $extension, string $path): bool
    {
        try {
            [$width, $height] = getimagesize($file) ?: [0, 0];

            if ($width < 1 || max($width, $height) <= self::MAX_SIDE || $width * $height > self::MAX_PIXELS) {
                return false;
            }

            $image = imagecreatefromstring((string) file_get_contents($file));

            if (! $image) {
                return false;
            }

            // Asked before scaling, which turns the picture to full colour.
            $fewColours = ! imageistruecolor($image);
            $scale = self::MAX_SIDE / max($width, $height);
            $smaller = imagescale($image, (int) round($width * $scale), (int) round($height * $scale), IMG_BICUBIC);

            // A PNG of few colours (a logo) comes back to few colours: made smaller in full colour it would grow.
            if ($extension === 'png' && $fewColours) {
                imagetruecolortopalette($smaller, true, 256);
            }

            // Transparency kept for PNG and WebP.
            imagealphablending($smaller, false);
            imagesavealpha($smaller, true);

            $written = match ($extension) {
                'png' => imagepng($smaller, $file.'.tmp', 9),
                'webp' => imagewebp($smaller, $file.'.tmp', 82),
                default => imagejpeg($smaller, $file.'.tmp', 82),
            };

            // Swapped in only once written whole, and only when it came out smaller; the original is kept first, out of
            // public reach (storage/app/private/picture-originals), so the change can be undone.
            if ($written && filesize($file.'.tmp') > 0 && filesize($file.'.tmp') < filesize($file)) {
                if (! Storage::disk('local')->put(self::ORIGINALS.'/'.$path, (string) file_get_contents($file))) {
                    @unlink($file.'.tmp');

                    return false;
                }

                return rename($file.'.tmp', $file);
            }

            @unlink($file.'.tmp');

            return false;
        } catch (Throwable $e) {
            @unlink($file.'.tmp');
            report($e);

            return false;
        }
    }
}
