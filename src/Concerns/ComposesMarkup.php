<?php

namespace Parity\Concerns;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Parity\Config\ConfigCollector;
use Parity\Contracts\Composable;
use Parity\Render\SchemaRenderer;
use Parity\Schema\SlotCollector;

/**
 * Schema-driven markup composition for Blade components.
 *
 * Classes using this trait must implement {@see Composable}.
 *
 * @method static array compose()
 *
 * @phpstan-require-implements Composable
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

    /** @var array<class-string, array<string, mixed>> */
    protected static array $composedSchemas = [];

    /** @var array<class-string, list<string>> */
    protected static array $publicPropNames = [];

    /** @var array<class-string, string> */
    protected static array $viewPaths = [];

    /** @var array<class-string, bool> */
    protected static array $themeViewExists = [];

    protected function bootComposition(): void
    {
        if ($this->composed) {
            return;
        }

        $this->composed = true;
        $this->schema = $this->resolvedSchema();
        $this->name = $this->resolveComponentName($this->schema);
        $this->prepare();
        $this->props = $this->collectPublicProps();
        $this->resolveElement();
        $this->build();
    }

    /**
     * Immutable compose() output, shared across instances of the same class.
     *
     * @return array<string, mixed>
     */
    protected function resolvedSchema(): array
    {
        return self::$composedSchemas[static::class] ??= static::compose();
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
        $rendered = $renderer->renderComponent($this->schema, $this->props, $this->name);
        $this->attr = $rendered['attributes'];
        $this->structure = $rendered['structure'];
        $this->slotElement = $this->schema['defaultSlot'] ?? null;

        app(ConfigCollector::class)->register($this->name, $this->schema);
    }

    public function shouldRenderElement(string $key, ?array $structure = null): bool
    {
        $structureToCheck = $structure ?? $this->structure;

        return isset($structureToCheck[$key]);
    }

    /**
     * @return \Closure|Htmlable|View|string
     */
    public function render()
    {
        return $this->renderComposed();
    }

    /**
     * @return \Closure|Htmlable|View|string
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
        return self::$themeViewExists[static::class] ??= view()->exists($this->resolveViewPath());
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
        if (isset(self::$viewPaths[static::class])) {
            return self::$viewPaths[static::class];
        }

        $reflection = new \ReflectionClass(static::class);
        $namespaceParts = explode('\\', $reflection->getNamespaceName());
        $componentsIndex = array_search('Components', $namespaceParts, true);

        if ($componentsIndex !== false && isset($namespaceParts[$componentsIndex + 1])) {
            $subNamespace = strtolower($namespaceParts[$componentsIndex + 1]);
            $path = "components.{$subNamespace}.{$this->name}";
        } else {
            $path = "components.{$this->name}";
        }

        return self::$viewPaths[static::class] = $path;
    }

    /**
     * @param  array<string, mixed>|null  $schema
     */
    protected function resolveComponentName(?array $schema = null): string
    {
        $schema ??= $this->schema;

        if (! empty($schema['name'])) {
            return $schema['name'];
        }

        $className = class_basename(static::class);

        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $className));
    }

    /** @return array<string, mixed> */
    protected function collectPublicProps(): array
    {
        $propNames = $this->publicPropNames();
        $props = [];

        foreach ($propNames as $name) {
            $props[$name] = $this->{$name};
        }

        return $props;
    }

    /** @return list<string> */
    protected function publicPropNames(): array
    {
        if (isset(self::$publicPropNames[static::class])) {
            return self::$publicPropNames[static::class];
        }

        $propNames = [];
        $reflection = new \ReflectionClass($this);

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();

            if (in_array($name, ['name', 'attr', 'structure', 'slotElement'], true)) {
                continue;
            }

            $propNames[] = $name;
        }

        return self::$publicPropNames[static::class] = $propNames;
    }
}
