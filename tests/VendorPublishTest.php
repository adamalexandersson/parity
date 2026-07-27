<?php

use Illuminate\Support\Facades\File;

afterEach(function () {
    if (File::exists(config_path('sprout.php'))) {
        File::delete(config_path('sprout.php'));
    }

    if (File::exists(config_path('sprout/presets.php'))) {
        File::delete(config_path('sprout/presets.php'));
    }

    if (File::isDirectory(config_path('sprout'))) {
        File::deleteDirectory(config_path('sprout'));
    }
});

it('publishes config files only for the sprout tag', function () {
    $this->artisan('vendor:publish', ['--tag' => 'sprout'])
        ->assertExitCode(0);

    expect(config_path('sprout.php'))->toBeFile()
        ->and(config_path('sprout/presets.php'))->toBeFile()
        ->and(app_path('Providers/SproutServiceProvider.php'))->not->toBeFile();
});
