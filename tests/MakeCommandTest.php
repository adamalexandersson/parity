<?php

use App\View\Components\ProbeButton;
use App\View\Components\Ui\ProbeChip;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->rootComponent = app_path('View/Components/ProbeButton.php');
    $this->uiComponent = app_path('View/Components/Ui/ProbeChip.php');
});

afterEach(function () {
    foreach ([$this->rootComponent, $this->uiComponent] as $path) {
        if (is_string($path) && File::exists($path)) {
            File::delete($path);
        }
    }

    $uiDir = app_path('View/Components/Ui');

    if (File::isDirectory($uiDir) && count(File::files($uiDir)) === 0) {
        File::deleteDirectory($uiDir);
    }
});

it('scaffolds a root component with a valid match chain', function () {
    $this->artisan('parity:make', ['name' => 'ProbeButton'])
        ->assertSuccessful();

    expect($this->rootComponent)->toBeFile()
        ->and(File::get($this->rootComponent))->toContain('namespace App\\View\\Components;')
        ->and(File::get($this->rootComponent))->toContain("->case('sm')->classes('text-sm')->end()");

    require_once $this->rootComponent;

    $schema = ProbeButton::compose();

    expect($schema['name'])->toBe('probe-button')
        ->and($schema)->toHaveKey('matches')
        ->and($schema['matches'])->not->toBeEmpty();
});

it('scaffolds ui components under the Ui namespace', function () {
    $this->artisan('parity:make', ['name' => 'ProbeChip', '--ui' => true])
        ->assertSuccessful();

    expect($this->uiComponent)->toBeFile()
        ->and(File::get($this->uiComponent))->toContain('namespace App\\View\\Components\\Ui;');

    require_once $this->uiComponent;

    $schema = ProbeChip::compose();

    expect($schema['name'])->toBe('probe-chip');
});
