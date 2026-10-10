<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteSettings as SettingsPage;
use App\Http\Middleware\SetLocale;
use App\Models\User;
use App\Support\SiteSettings;
use App\Support\Wording;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WordingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // What was read is kept for the life of the PHP process; the next test starts clean.
        Wording::refresh();

        parent::tearDown();
    }

    public function test_a_phrase_left_alone_reads_as_it_always_did(): void
    {
        $this->withUnencryptedCookie(SetLocale::COOKIE, 'th')->get('/')->assertSee('ยินดีต้อนรับสู่');
        $this->withUnencryptedCookie(SetLocale::COOKIE, 'en')->get('/')->assertSee('Welcome to');
    }

    public function test_a_rewritten_phrase_wins_in_the_language_it_was_written_for(): void
    {
        SiteSettings::save(['wording' => [['key' => 'Welcome to', 'th' => 'โรงพยาบาลของชุมชน', 'en' => null]]]);

        $this->withUnencryptedCookie(SetLocale::COOKIE, 'th')->get('/')->assertSee('โรงพยาบาลของชุมชน')->assertDontSee('ยินดีต้อนรับสู่');
        // English was left empty, so it keeps its usual wording.
        $this->withUnencryptedCookie(SetLocale::COOKIE, 'en')->get('/')->assertSee('Welcome to');
    }

    public function test_placeholders_keep_working_in_the_new_wording(): void
    {
        SiteSettings::save(['wording' => [['key' => 'Call :number', 'th' => 'โทรหาเราที่ :number', 'en' => 'Ring us on :number']], 'contact.phone' => '044-579076']);

        $this->withUnencryptedCookie(SetLocale::COOKIE, 'th')->get('/')->assertSee('โทรหาเราที่ 044-579076');
        $this->withUnencryptedCookie(SetLocale::COOKIE, 'en')->get('/')->assertSee('Ring us on 044-579076');
    }

    public function test_what_an_administrator_writes_is_printed_as_text_never_run(): void
    {
        SiteSettings::save(['wording' => [['key' => 'Welcome to', 'en' => '<script>alert(1)</script>']]]);

        $this->withUnencryptedCookie(SetLocale::COOKIE, 'en')->get('/')->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_a_phrase_is_found_by_how_it_reads_in_either_language(): void
    {
        $this->assertArrayHasKey('Contact us', Wording::search('ติดต่อเรา'));
        $this->assertArrayHasKey('Contact us', Wording::search('contact'));
        $this->assertSame([], Wording::search('x'));
        $this->assertStringContainsString('ติดต่อเรา', Wording::label('Contact us'));
    }

    public function test_only_phrases_of_the_site_are_kept_each_once_with_something_written(): void
    {
        $rows = Wording::clean([
            ['key' => 'Contact us', 'th' => ' ติดต่อโรงพยาบาล ', 'en' => ''],
            ['key' => 'Contact us', 'th' => 'ซ้ำ'],
            ['key' => 'Not a phrase of the site', 'th' => 'x'],
            ['key' => 'Home', 'th' => '', 'en' => '  '],
            'nonsense',
        ]);

        $this->assertSame([['key' => 'Contact us', 'th' => 'ติดต่อโรงพยาบาล', 'en' => null]], $rows);
    }

    public function test_an_administrator_rewrites_a_phrase_from_the_settings_and_takes_it_back(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(SettingsPage::class)
            ->fillForm(['wording_rows' => [['key' => 'Contact us', 'th' => 'ติดต่อโรงพยาบาล', 'en' => null]]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('ติดต่อโรงพยาบาล', Wording::rows()[0]['th']);
        $this->withUnencryptedCookie(SetLocale::COOKIE, 'th')->get('/')->assertSee('ติดต่อโรงพยาบาล');

        Livewire::test(SettingsPage::class)->fillForm(['wording_rows' => []])->call('save')->assertHasNoFormErrors();

        $this->assertSame([], Wording::rows());
        $this->withUnencryptedCookie(SetLocale::COOKIE, 'th')->get('/')->assertSee('ติดต่อเรา');
    }

    public function test_the_section_reads_in_thai(): void
    {
        app()->setLocale('th');

        $this->actingAs(User::factory()->admin()->create())->get('/admin/site-settings')->assertSee('ถ้อยคำบนเว็บไซต์');
    }
}
