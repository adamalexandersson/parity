<?php

namespace Sprout\Tests;

use Illuminate\Support\HtmlString;
use Illuminate\View\ComponentAttributeBag;
use Orchestra\Testbench\TestCase;
use Sprout\Providers\SproutServiceProvider;
use Sprout\Tests\Fixtures\ShellLinkableTestComponent;
use Sprout\Tests\Fixtures\ShellOverrideTestComponent;
use Sprout\Tests\Fixtures\ShellSlotTestComponent;
use Sprout\Tests\Fixtures\ShellStructureTestComponent;
use Sprout\View\Component as SproutComponent;

class ShellFallbackTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            SproutServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('view.paths', [
            __DIR__.'/views',
        ]);
    }

    public function test_structure_component_uses_default_shell_when_theme_view_missing(): void
    {
        $html = $this->renderComponent(
            new ShellStructureTestComponent,
            'Inner content',
        );

        $this->assertStringContainsString('<section', $html);
        $this->assertStringContainsString('structure-test', $html);
        $this->assertStringContainsString('Inner content', $html);
    }

    public function test_slot_only_component_uses_default_shell_when_theme_view_missing(): void
    {
        $html = $this->renderComponent(
            new ShellSlotTestComponent,
            'Heading text',
        );

        $this->assertStringContainsString('<h2', $html);
        $this->assertStringContainsString('slot-test', $html);
        $this->assertStringContainsString('Heading text', $html);
    }

    public function test_theme_view_overrides_default_shell(): void
    {
        $html = $this->renderComponent(
            new ShellOverrideTestComponent,
            'Override content',
        );

        $this->assertStringContainsString('data-theme-override="true"', $html);
        $this->assertStringContainsString('Override content', $html);
        $this->assertStringNotContainsString('<section', $html);
    }

    public function test_linkable_component_resolves_root_tag_to_anchor(): void
    {
        $html = $this->renderComponent(new ShellLinkableTestComponent);

        $this->assertStringContainsString('<a', $html);
        $this->assertStringContainsString('linkable-test', $html);
        $this->assertStringNotContainsString('<button', $html);
    }

    protected function renderComponent(SproutComponent $component, string $slot = ''): string
    {
        $render = $component->render();

        $data = [
            'attributes' => new ComponentAttributeBag,
            'slot' => new HtmlString($slot),
        ];

        if ($render instanceof \Closure) {
            return (string) $render($data);
        }

        return (string) $render->with($data)->render();
    }
}
