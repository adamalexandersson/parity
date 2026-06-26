<?php

namespace Sprout\Tests;

use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;
use Sprout\Providers\SproutServiceProvider;

class VendorPublishTest extends TestCase
{
    protected function tearDown(): void
    {
        if (File::exists(config_path('sprout.php'))) {
            File::delete(config_path('sprout.php'));
        }

        if (File::exists(config_path('sprout/common.php'))) {
            File::delete(config_path('sprout/common.php'));
        }

        if (File::isDirectory(config_path('sprout'))) {
            File::deleteDirectory(config_path('sprout'));
        }

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            SproutServiceProvider::class,
        ];
    }

    public function test_sprout_tag_publishes_config_files_only(): void
    {
        $this->artisan('vendor:publish', ['--tag' => 'sprout'])
            ->assertExitCode(0);

        $this->assertFileExists(config_path('sprout.php'));
        $this->assertFileExists(config_path('sprout/common.php'));
        $this->assertFileDoesNotExist(app_path('Providers/SproutServiceProvider.php'));
    }
}
