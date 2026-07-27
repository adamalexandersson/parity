<?php

use Illuminate\Support\HtmlString;
use Illuminate\View\ComponentAttributeBag;
use Parity\Component;
use Parity\Registries\TransformRegistry;
use Parity\Render\SchemaRenderer;
use Parity\Support\ClassFactory;
use Parity\View\Component as ViewComponent;

it('renders a preset match with no presets configured', function () {
    config(['parity.presets' => []]);

    $renderer = new SchemaRenderer(new TransformRegistry);
    $classes = new ClassFactory;

    $reflection = new ReflectionClass($renderer);
    $method = $reflection->getMethod('applyPresetMatch');
    $method->setAccessible(true);

    $method->invoke($renderer, [
        'preset' => 'cols',
        'props' => ['cols'],
        'condition' => null,
    ], ['cols' => 2], $classes);

    expect($classes->get())->toBe('');
});

it('renders a view component shell with package defaults and no published config', function () {
    config([
        'parity.presets' => [],
        'parity.tokens' => [],
        'parity.shell_view' => 'Parity::shell',
        'parity.classes.strategy' => 'passthrough',
    ]);

    $component = new class extends ViewComponent
    {
        /** @return array<string, mixed> */
        public static function compose(): array
        {
            return Component::make('zero-config', tag: 'div')
                ->classes('block')
                ->slot()
                ->toSchema();
        }
    };

    $render = $component->render();
    $data = [
        'attributes' => new ComponentAttributeBag,
        'slot' => new HtmlString(''),
    ];

    $html = $render instanceof Closure
        ? (string) $render($data)
        : (string) $render->with($data)->render();

    expect($html)->toContain('class="block"')
        ->and($html)->toContain('<div');
});
