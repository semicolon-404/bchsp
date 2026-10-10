<?php

namespace Tests;

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
}
