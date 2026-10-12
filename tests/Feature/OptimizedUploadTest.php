<?php

namespace Tests\Feature;

use App\Services\ImageOptimizer;
use App\Support\OptimizedUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OptimizedUploadTest extends TestCase
{
    /** A photograph-like picture: noise does not compress to nothing the way a blank one does. */
    private function photo(string $name, int $width, int $height, string $type = 'jpeg'): UploadedFile
    {
        $image = imagecreatetruecolor($width, $height);

        for ($i = 0; $i < 400; $i++) {
            imagefilledrectangle($image, random_int(0, $width), random_int(0, $height), random_int(0, $width), random_int(0, $height), imagecolorallocate($image, random_int(0, 255), random_int(0, 255), random_int(0, 255)));
        }

        $path = tempnam(sys_get_temp_dir(), 'photo');
        $type === 'png' ? imagepng($image, $path) : imagejpeg($image, $path, 95);

        return new UploadedFile($path, $name, 'image/'.$type, null, true);
    }

    public function test_a_large_photo_is_kept_smaller_and_as_webp(): void
    {
        Storage::fake('public');
        $photo = $this->photo('ward.jpg', 4000, 3000);

        $path = OptimizedUpload::store($photo, 'public', 'announcements/pictures');

        $this->assertStringStartsWith('announcements/pictures/', $path);
        $this->assertStringEndsWith('.webp', $path);
        Storage::disk('public')->assertExists($path);

        [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($path));

        $this->assertSame([1920, 1440], [$width, $height]);
        $this->assertLessThan($photo->getSize(), Storage::disk('public')->size($path));
    }

    public function test_the_name_it_is_kept_under_cannot_be_guessed_from_the_upload(): void
    {
        Storage::fake('public');

        $path = OptimizedUpload::store($this->photo('patient-somchai.jpg', 2400, 1600), 'public', 'announcements/pictures');

        $this->assertStringNotContainsString('somchai', $path);
    }

    public function test_a_png_keeps_its_transparency(): void
    {
        $image = imagecreatetruecolor(2200, 800);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
        $path = tempnam(sys_get_temp_dir(), 'logo');
        imagepng($image, $path);

        $webp = app(ImageOptimizer::class)->toWebp($path, 'image/png');

        $this->assertNotNull($webp);

        $back = imagecreatefromstring($webp['data']);

        // 127 is fully clear; WebP keeps it to within a step.
        $this->assertGreaterThanOrEqual(120, imagecolorsforindex($back, imagecolorat($back, 10, 10))['alpha']);
    }

    public function test_what_is_not_a_plain_picture_is_kept_as_it_was(): void
    {
        Storage::fake('public');

        $gif = UploadedFile::fake()->image('moving.gif', 300, 300);
        $path = OptimizedUpload::store($gif, 'public', 'posters');

        $this->assertStringEndsWith('.gif', $path);
        $this->assertNull(app(ImageOptimizer::class)->toWebp($gif->getRealPath(), 'image/gif'));
    }

    public function test_a_file_that_only_claims_to_be_a_picture_is_not_decoded(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'fake');
        file_put_contents($path, '<?php echo "not a picture";');

        $this->assertNull(app(ImageOptimizer::class)->toWebp($path, 'image/jpeg'));
    }
}
