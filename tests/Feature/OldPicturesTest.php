<?php

namespace Tests\Feature;

use App\Support\OldPictures;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** A picture kept thousands of dots wide is made smaller where it lies: same name, same format, nothing to change. */
class OldPicturesTest extends TestCase
{
    public function test_a_huge_old_picture_is_made_smaller_under_the_same_name_and_a_small_one_is_left(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $disk = Storage::disk('public');

        // A wide PNG full of noise (so it is large), and a small one.
        $huge = imagecreatetruecolor(4000, 1000);
        for ($i = 0; $i < 60000; $i++) {
            imagesetpixel($huge, random_int(0, 3999), random_int(0, 999), random_int(0, 0xFFFFFF));
        }
        ob_start();
        imagepng($huge);
        $disk->put('site/logo.png', (string) ob_get_clean());
        ob_start();
        imagepng(imagecreatetruecolor(40, 40));
        $disk->put('site/small.png', (string) ob_get_clean());
        $before = $disk->size('site/logo.png');
        $this->assertGreaterThan(300 * 1024, $before);

        $this->assertSame(1, OldPictures::shrink());

        [$width] = getimagesize($disk->path('site/logo.png'));
        $this->assertSame(1920, $width);
        $this->assertLessThan($before, $disk->size('site/logo.png'));
        $this->assertSame('image/png', mime_content_type($disk->path('site/logo.png')));
        // The original kept, out of public reach, to put back.
        Storage::disk('local')->assertExists(OldPictures::ORIGINALS.'/site/logo.png');
        $this->assertSame($before, Storage::disk('local')->size(OldPictures::ORIGINALS.'/site/logo.png'));

        // Done once: nothing left to do.
        $this->assertSame(0, OldPictures::shrink());
    }

    public function test_a_logo_of_few_colours_stays_few_colours_and_comes_out_smaller(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $disk = Storage::disk('public');

        // A palette PNG, as logos are: wide, a few colours in bands of noise so it is large.
        $logo = imagecreate(6000, 1200);
        $colours = array_map(fn ($i) => imagecolorallocate($logo, $i * 30, 255 - $i * 30, 120), range(0, 7));
        for ($i = 0; $i < 400000; $i++) {
            imagesetpixel($logo, random_int(0, 5999), random_int(0, 1199), $colours[array_rand($colours)]);
        }
        ob_start();
        imagepng($logo, null, 9);
        $disk->put('site/logo.png', (string) ob_get_clean());
        $before = $disk->size('site/logo.png');
        $this->assertGreaterThan(300 * 1024, $before);

        // Scaled in full colour it would grow and be left; kept to few colours it is made smaller.
        $this->assertSame(1, OldPictures::shrink());
        $this->assertLessThan($before, $disk->size('site/logo.png'));
    }
}
