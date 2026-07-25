<?php

use Illuminate\Support\HtmlString;
use Illuminate\View\ComponentAttributeBag;
use Sprout\Tests\Fixtures\ShellLinkableTestComponent;
use Sprout\Tests\Fixtures\ShellOverrideTestComponent;
use Sprout\Tests\Fixtures\ShellSlotTestComponent;
use Sprout\Tests\Fixtures\ShellStructureTestComponent;
use Sprout\View\Component as SproutComponent;

beforeEach(function () {
    $this->app['config']->set('view.paths', [
        __DIR__.'/views',
    ]);
});

function renderSproutComponent(SproutComponent $component, string $slot = ''): string
{
    $render = $component->render();

    $data = [
        'attributes' => new ComponentAttributeBag,
        'slot' => new HtmlString($slot),
    ];

    if ($render instanceof Closure) {
        return (string) $render($data);
    }

    return (string) $render->with($data)->render();
}

it('uses the default shell when a theme view is missing for structure components', function () {
    $html = renderSproutComponent(new ShellStructureTestComponent, 'Inner content');

    expect($html)->toContain('<section')
        ->and($html)->toContain('structure-test')
        ->and($html)->toContain('Inner content');
});

it('uses the default shell when a theme view is missing for slot-only components', function () {
    $html = renderSproutComponent(new ShellSlotTestComponent, 'Heading text');

    expect($html)->toContain('<h2')
        ->and($html)->toContain('slot-test')
        ->and($html)->toContain('Heading text');
});

it('allows a theme view to override the default shell', function () {
    $html = renderSproutComponent(new ShellOverrideTestComponent, 'Override content');

    expect($html)->toContain('data-theme-override="true"')
        ->and($html)->toContain('Override content')
        ->and($html)->not->toContain('<section');
});

it('resolves a linkable root tag to an anchor', function () {
    $html = renderSproutComponent(new ShellLinkableTestComponent);

    expect($html)->toContain('<a')
        ->and($html)->toContain('linkable-test')
        ->and($html)->not->toContain('<button');
});
