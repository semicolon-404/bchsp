<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The pages are tested as written; whether the stylesheet has been built on this machine is not their business.
        $this->withoutVite();
    }
}
