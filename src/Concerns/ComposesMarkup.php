<?php

namespace Parity\Concerns;

use Illuminate\Contracts\Support\Htmlable;
use Parity\Config\ConfigCollector;
use Parity\Render\SchemaRenderer;
use Parity\Schema\SlotCollector;

/**
 * Schema-driven markup composition for Blade components.
 *
 * Classes using this trait must implement {@see \Parity\Contracts\Composable}.
 *
 * @method static array compose()
 *
 * @phpstan-require-implements \Parity\Contracts\Composable
 */
trait ComposesMarkup
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

    protected bool $composed = false;

    protected function bootComposition(): void
    {
        if ($this->composed) {
            return;
        }

        $this->composed = true;
        $this->name = $this->resolveComponentName();
        $this->schema = static::compose();
        $this->prepare();
        $this->props = $this->collectPublicProps();
        $this->resolveElement();
        $this->build();
    }

    protected function prepare(): void {}

    /**
     * @return array<string, mixed>
     */
    public function data()
    {
        return $this->dataComposed();
    }

    /**
     * @return array<string, mixed>
     */
    protected function dataComposed()
    {
        $this->bootComposition();

        return parent::data();
    }

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
        $this->structure = $renderer->renderStructure($this->schema, $this->props, $this->name);
        $this->slotElement = $this->schema['defaultSlot'] ?? null;

        app(ConfigCollector::class)->register($this->name, $this->schema);
    }

    public function shouldRenderElement(string $key, ?array $structure = null): bool
    {
        $structureToCheck = $structure ?? $this->structure;

        return isset($structureToCheck[$key]);
    }

    /**
     * @return \Closure|\Illuminate\Contracts\Support\Htmlable|\Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        return $this->renderComposed();
    }

    /**
     * @return \Closure|\Illuminate\Contracts\Support\Htmlable|\Illuminate\Contracts\View\View|string
     */
    protected function renderComposed()
    {
        $this->bootComposition();

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

        return view('Parity::structure', [
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
        return config('parity.shell_view', 'Parity::shell');
    }

    /** @param array<string, mixed> $data */
    protected function resolveNamedSlots(array $data): array
    {
        $slots = [];

        if (isset($data['__laravel_slots']) && is_array($data['__laravel_slots'])) {
            $slots = collect($data['__laravel_slots'])
                ->except(['__default'])
                ->filter(fn ($value) => $value instanceof Htmlable)
                ->all();
        } else {
            $slots = collect($data)
                ->except(['slot', 'attributes', '__laravel_slots'])
                ->filter(fn ($value) => $value instanceof Htmlable)
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
        $schema = static::compose();

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
