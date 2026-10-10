<?php

namespace Tests;

use App\Support\HomeLayout;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Livewire\Mechanisms\HandleComponents\HandleComponents;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The pages are tested as written; whether the stylesheet has been built on this machine is not their business.
        $this->withoutVite();

        // Livewire keeps the screen it is working on in a static list for the life of the PHP process. On a server
        // that is one request; here it would be every test before this one, and App\Support\StaffLog asks it
        // "is this a screen of the panel?".
        HandleComponents::$componentStack = [];
    }

    /** Switch on sections of the home page that are off until an administrator asks for them, each in its usual place. */
    protected function showOnHome(string ...$sections): void
    {
        SiteSettings::save(['home.layout' => array_map(
            fn (string $key) => ['key' => $key, 'shown' => in_array($key, $sections, true) || ! in_array($key, HomeLayout::OFF_AT_FIRST, true)],
            array_keys(HomeLayout::SECTIONS),
        )]);
    }
}
