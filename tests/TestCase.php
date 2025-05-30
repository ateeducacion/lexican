<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
// 'Illuminate\Foundation\Testing\TestCase::tearDown()'.intelephense(1038)


abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
    parent::setUp();
    //some other actions..
    }

    // solucionar problema no encutra "config"
    protected function tearDown(): void
    {
        $config = app('config');
        parent::tearDown();
        app()->instance('config', $config);
    }
}
