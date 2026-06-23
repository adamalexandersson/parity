<?php

namespace Sprout\View;

use Illuminate\View\Component as BladeComponent;
use Sprout\Config\ConfigCollector;
use Sprout\Render\SchemaRenderer;
use Sprout\Schema\SlotCollector;

abstract class Component extends BladeComponent
{
    public string $name = '';

    /** @var array<string, mixed> */
    public array $attr = [];

    /** @var array<string, mixed> */
    public array $structure = [];

    public ?string $slotElement = null;

    /** @var array<string, mixed> */
    protected array $schema = [];

    /** @var array<string, mixed> */
    protected array $props = [];

    public function __construct(...$args)
    {
        $this->name = $this->resolveComponentName();
        $this->schema = static::schema();
        $this->initialize(...$args);
        $this->props = $this->collectPublicProps();
        $this->resolveElement();
        $this->build();
    }

    /** @return array<string, mixed> */
    abstract public static function schema(): array;

    protected function initialize(...$args): void {}

    protected function resolveElement(): void
    {
        if (! property_exists($this, 'element')) {
            return;
        }

        $linkable = $this->resolveLinkableConfig();

        if ($linkable) {
            $prop = $linkable['prop'] ?? 'link';
            $link = $this->props[$prop] ?? null;

            if (is_string($link)) {
                $link = ['href' => $link];
            }

            if (is_object($link)) {
                $link = (array) $link;
            }

            if (! empty($link['href']) || ! empty($link['url'])) {
                $this->element = $linkable['tag'] ?? 'a';

                return;
            }
        }

        if ($this->element === null || $this->element === false || $this->element === '') {
            $this->element = $this->resolveSchemaTag();
        }
    }

    protected function resolveSchemaTag(): string
    {
        return $this->schema['tag'] ?? 'div';
    }

    /** @return array{prop?: string, tag?: string}|null */
    protected function resolveLinkableConfig(): ?array
    {
        if (! empty($this->schema['linkable']) && is_array($this->schema['linkable'])) {
            return $this->schema['linkable'];
        }

        return null;
    }

    protected function build(): void
    {
        $renderer = app(SchemaRenderer::class);
        $this->attr = $renderer->renderComponentAttributes($this->schema, $this->props, $this->name);
        $this->structure = $renderer->renderStructure($this->schema, $this->props);
        $this->slotElement = $this->schema['defaultSlot'] ?? null;

        app(ConfigCollector::class)->register($this->name, $this->schema);
    }

    public function shouldRenderElement(string $key, ?array $structure = null): bool
    {
        $structureToCheck = $structure ?? $this->structure;

        return isset($structureToCheck[$key]);
    }

    public function render()
    {
        if ($this->structure !== []) {
            return function (array $data) {
                $content = $this->renderStructureContent($data);

                if ($this->hasThemeView()) {
                    $data['content'] = $content;

                    return $this->view($this->resolveViewPath(), $data);
                }

                return $this->renderDefaultShell($data, $content);
            };
        }

        if ($this->hasThemeView()) {
            return $this->view($this->resolveViewPath());
        }

        return function (array $data) {
            return $this->renderDefaultShell($data);
        };
    }

    /** @param array<string, mixed> $data */
    protected function renderStructureContent(array $data): string
    {
        $namedSlots = $this->resolveNamedSlots($data);

        return view('Sprout::structure', [
            'structure' => $this->structure,
            'slotElement' => $this->slotElement,
            'slot' => $data['slot'] ?? null,
            'namedSlots' => $namedSlots,
            'props' => $this->props,
            'shouldRenderElement' => fn ($key, $struct = null) => $this->shouldRenderElement($key, $struct),
        ])->render();
    }

    protected function hasThemeView(): bool
    {
        return view()->exists($this->resolveViewPath());
    }

    public function resolveRootTag(): string
    {
        if (property_exists($this, 'element')) {
            $element = $this->element;

            if ($element !== null && $element !== false && $element !== '') {
                return (string) $element;
            }
        }

        return $this->resolveSchemaTag();
    }

    /** @param array<string, mixed> $data */
    protected function renderDefaultShell(array $data, ?string $content = null): string
    {
        return view($this->resolveShellViewPath(), [
            'element' => $this->resolveRootTag(),
            'attr' => $this->attr,
            'attributes' => $data['attributes'] ?? null,
            'content' => $content,
            'slot' => $data['slot'] ?? null,
        ])->render();
    }

    protected function resolveShellViewPath(): string
    {
        return config('sprout.shell_view', 'Sprout::shell');
    }

    /** @param array<string, mixed> $data */
    protected function resolveNamedSlots(array $data): array
    {
        $slots = [];

        if (isset($data['__laravel_slots']) && is_array($data['__laravel_slots'])) {
            $slots = collect($data['__laravel_slots'])
                ->except(['__default'])
                ->filter(fn ($value) => $value instanceof \Illuminate\Contracts\Support\Htmlable)
                ->all();
        } else {
            $slots = collect($data)
                ->except(['slot', 'attributes', '__laravel_slots'])
                ->filter(fn ($value) => $value instanceof \Illuminate\Contracts\Support\Htmlable)
                ->all();
        }

        $declared = $this->schema['namedSlots'] ?? SlotCollector::collect($this->schema);

        if ($declared === []) {
            return $slots;
        }

        return array_intersect_key($slots, array_flip($declared));
    }

    protected function resolveViewPath(): string
    {
        $reflection = new \ReflectionClass(static::class);
        $namespaceParts = explode('\\', $reflection->getNamespaceName());
        $componentsIndex = array_search('Components', $namespaceParts, true);

        if ($componentsIndex !== false && isset($namespaceParts[$componentsIndex + 1])) {
            $subNamespace = strtolower($namespaceParts[$componentsIndex + 1]);

            return "components.{$subNamespace}.{$this->name}";
        }

        return "components.{$this->name}";
    }

    protected function resolveComponentName(): string
    {
        $schema = static::schema();

        if (! empty($schema['name'])) {
            return $schema['name'];
        }

        $className = class_basename(static::class);

        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $className));
    }

    /** @return array<string, mixed> */
    protected function collectPublicProps(): array
    {
        $props = [];
        $reflection = new \ReflectionClass($this);

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();

            if (in_array($name, ['name', 'attr', 'structure', 'slotElement'], true)) {
                continue;
            }

            $props[$name] = $property->getValue($this);
        }

        return $props;
    }
}
