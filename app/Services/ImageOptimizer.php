<?php

namespace App\Services;

use Throwable;

/**
 * Shrinks uploaded photos: at most 1920px on the long side, re-encoded as
 * WebP. A phone photo typically drops from several megabytes to a few
 * hundred kilobytes, which is the biggest single win for page speed on
 * mobile data. Uses PHP's GD extension only.
 */
class ImageOptimizer
{
    private const MAX_SIDE = 1920;

    private const QUALITY = 82;

    /** Above this, decoding would need more memory than is sensible; keep the original. */
    private const MAX_PIXELS = 40_000_000;

    /**
     * @return array{data: string, width: int, height: int}|null null means "keep the original file"
     */
    public function toWebp(string $path, ?string $mime): ?array
    {
        // Animated GIFs would lose their animation; leave them alone.
        if (! function_exists('imagewebp') || ! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return null;
        }

        try {
            [$width, $height] = getimagesize($path) ?: [0, 0];

            if ($width < 1 || $width * $height > self::MAX_PIXELS) {
                return null;
            }

            $image = imagecreatefromstring((string) file_get_contents($path));

            if (! $image) {
                return null;
            }

            $image = $this->applyExifOrientation($image, $path, $mime);
            $width = imagesx($image);
            $height = imagesy($image);

            $scale = min(1, self::MAX_SIDE / max($width, $height));

            if ($scale < 1) {
                $image = imagescale($image, (int) round($width * $scale), (int) round($height * $scale), IMG_BICUBIC);
            }

            // WebP needs true colour; keep transparency from PNGs.
            imagepalettetotruecolor($image);
            imagealphablending($image, false);
            imagesavealpha($image, true);

            ob_start();
            imagewebp($image, null, self::QUALITY);
            $data = (string) ob_get_clean();

            $result = ['data' => $data, 'width' => imagesx($image), 'height' => imagesy($image)];
            imagedestroy($image);

            // A small, already well-compressed image can come out larger. Then the original wins.
            if ($data === '' || ($scale === 1 && strlen($data) >= filesize($path))) {
                return null;
            }

            return $result;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /** Phone cameras store "rotate me" in EXIF instead of rotating the pixels; GD ignores that unless told. */
    private function applyExifOrientation(\GdImage $image, string $path, string $mime): \GdImage
    {
        if ($mime !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = (@exif_read_data($path))['Orientation'] ?? 1;

        $degrees = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        return $degrees === 0 ? $image : (imagerotate($image, $degrees, 0) ?: $image);
    }
}
