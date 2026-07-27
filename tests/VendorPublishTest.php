<?php

use Illuminate\Support\Facades\File;

afterEach(function () {
    if (File::exists(config_path('parity.php'))) {
        File::delete(config_path('parity.php'));
    }

    if (File::exists(config_path('parity/presets.php'))) {
        File::delete(config_path('parity/presets.php'));
    }

    if (File::isDirectory(config_path('parity'))) {
        File::deleteDirectory(config_path('parity'));
    }
});

it('publishes only the root config for the parity tag', function () {
    $this->artisan('vendor:publish', ['--tag' => 'parity'])
        ->assertExitCode(0);

    expect(config_path('parity.php'))->toBeFile()
        ->and(config_path('parity/presets.php'))->not->toBeFile()
        ->and(app_path('Providers/ParityServiceProvider.php'))->not->toBeFile();
});

it('publishes the presets stub for the parity-presets tag', function () {
    $this->artisan('vendor:publish', ['--tag' => 'parity-presets'])
        ->assertExitCode(0);

    expect(config_path('parity/presets.php'))->toBeFile()
        ->and(config_path('parity.php'))->not->toBeFile();
});
