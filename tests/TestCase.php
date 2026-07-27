<?php

namespace Parity\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Parity\Providers\ParityServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ParityServiceProvider::class,
        ];
    }
}
