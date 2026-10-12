<?php

namespace Tests\Feature;

use App\Mail\SystemProblems;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** The site kept on a phone's home screen, with the numbers reachable offline; and the morning email when something is wrong. */
class HomeScreenAndAlertsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_phone_is_told_what_the_site_is_called_and_looks_like(): void
    {
        SiteSettings::save(['app.name' => 'Buachet Hospital']);

        $manifest = $this->get('/manifest.webmanifest')->assertOk()->assertHeader('Content-Type', 'application/manifest+json; charset=UTF-8')->json();

        $this->assertSame('Buachet Hospital', $manifest['name']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame(route('home'), $manifest['start_url']);
        $this->assertSame(['192x192', '512x512'], array_column($manifest['icons'], 'sizes'));

        $this->get('/')
            ->assertSee('<link rel="manifest" href="'.route('manifest').'">', false)
            ->assertSee('<meta name="theme-color"', false)
            ->assertSee('data-worker="'.route('worker').'"', false);
    }

    public function test_the_picture_for_a_home_screen_is_drawn_with_or_without_a_logo(): void
    {
        Storage::fake('public');

        $plain = $this->get('/icon-192.png')->assertOk()->assertHeader('Content-Type', 'image/png')->getContent();
        $this->assertSame([192, 192], array_slice(getimagesizefromstring($plain), 0, 2));

        Storage::disk('public')->put('site/logo.png', UploadedFile::fake()->image('logo.png', 300, 120)->getContent());
        SiteSettings::save(['site.logo' => 'site/logo.png']);

        $withLogo = $this->get('/icon-512.png')->assertOk()->getContent();
        $this->assertSame([512, 512], array_slice(getimagesizefromstring($withLogo), 0, 2));

        // Only the sizes on offer are drawn: nobody can ask the server for a picture a mile wide.
        $this->get('/icon-99999.png')->assertNotFound();
        $this->get('/icon-abc.png')->assertNotFound();
    }

    public function test_with_no_connection_the_phone_shows_the_numbers_it_kept(): void
    {
        SiteSettings::save(['contact.phone' => '044-579076', 'contact.emergency_phone' => '1669']);

        $this->get('/sw.js')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/javascript; charset=UTF-8')
            ->assertSee(json_encode(route('offline'), JSON_UNESCAPED_SLASHES), false)
            ->assertSee("event.request.mode !== 'navigate'", false);

        $page = $this->get('/offline')->assertOk()->assertSee('No connection')->assertSee('href="tel:1669"', false)->assertSee('href="tel:044579076"', false)->getContent();

        // It stands on its own: nothing to fetch when there is nothing to fetch with.
        // (Not checked for scripts: in a run of every test, Livewire adds its own to whatever page comes after one of its screens.)
        $this->assertStringNotContainsString('<link rel="stylesheet"', $page);
        $this->assertStringNotContainsString('/build/assets/', $page);
    }

    public function test_administrators_are_told_by_email_when_something_is_wrong_and_not_when_all_is_well(): void
    {
        Mail::fake();
        User::factory()->admin()->create(['email' => 'admin@example.org']);
        User::factory()->create(['email' => 'author@example.org']);

        // A new site has no backup yet, and its scheduled work has never run: that is something wrong.
        $this->artisan('health:notify')->assertSuccessful();

        Mail::assertSent(SystemProblems::class, fn (SystemProblems $mail) => $mail->hasTo('admin@example.org') && ! $mail->hasTo('author@example.org') && $mail->problems !== []);

        $text = (new SystemProblems([['name' => 'Backup', 'state' => 'problem', 'found' => 'None yet', 'advice' => 'Take one now.']]))->render();

        $this->assertStringContainsString('Backup: None yet', $text);
        $this->assertStringContainsString('Take one now.', $text);
        $this->assertStringContainsString('/admin/system-status', $text);
    }

    public function test_with_nobody_to_tell_nothing_is_sent(): void
    {
        Mail::fake();
        User::factory()->admin()->create(['email' => null, 'username' => 'admin']);

        $this->artisan('health:notify')->assertSuccessful();

        Mail::assertNothingSent();
    }
}
