<?php

namespace Sprout\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Sprout\Providers\SproutServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            SproutServiceProvider::class,
        ];
    }
}
