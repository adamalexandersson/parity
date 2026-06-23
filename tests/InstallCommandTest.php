<?php

namespace Sprout\Tests;

use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;
use Sprout\Console\InstallCommand;
use Sprout\Providers\SproutServiceProvider;

class InstallCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        if (File::exists(app_path('Providers/SproutServiceProvider.php'))) {
            File::delete(app_path('Providers/SproutServiceProvider.php'));
        }

        if (File::exists(config_path('sprout/common.php'))) {
            File::delete(config_path('sprout/common.php'));
        }

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            SproutServiceProvider::class,
        ];
    }

    public function test_install_publishes_theme_files(): void
    {
        $this->artisan('sprout:install')
            ->assertExitCode(0);

        $this->assertFileExists(app_path('Providers/SproutServiceProvider.php'));
        $this->assertFileExists(config_path('sprout/common.php'));
        $this->assertStringContainsString(
            'Theme integration for Sprout',
            File::get(app_path('Providers/SproutServiceProvider.php'))
        );
    }
}
