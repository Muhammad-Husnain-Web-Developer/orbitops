<?php

namespace Tests;

use App\Support\CurrentWorkspace;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Requests set the tenant themselves; tests start without one.
        app(CurrentWorkspace::class)->set(null);
    }
}
