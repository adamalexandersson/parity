<?php

namespace Sprout\Render;

use Sprout\Concerns\EvaluatesConditions;
use Sprout\Exceptions\SchemaException;
use Sprout\Registries\TransformRegistry;
use Sprout\Support\AttributeFactory;
use Sprout\Support\ClassFactory;
use Sprout\Support\IdInterpolator;
use Sprout\Support\InlineStyleFactory;
use Sprout\Support\InstanceIds;

class SchemaRenderer
{
    use EvaluatesConditions;

    /** @var array<string, mixed> */
    protected array $contextProps = [];

    protected ?InstanceIds $instanceIds = null;

    public function __construct(
        protected TransformRegistry $transforms,
    ) {}

    /** @param array<string, mixed> $schema */
    public function renderComponentAttributes(array $schema, array $props, ?string $componentName = null): array
    {
        $this->contextProps = $props;
        $this->instanceIds = new InstanceIds($componentName ?? ($schema['name'] ?? 'component'), $props);
        $this->predeclareIds($schema);
        $classes = new ClassFactory;
        $styles = new InlineStyleFactory;
        $attr = new AttributeFactory;

        $this->applyClassRules($schema['classRules'] ?? [], $props, $classes);
        $this->applyMatches($schema['matches'] ?? [], $props, $classes, $attr, $styles, $schema);

        if (! empty($props['class'])) {
            $classes->apply((string) $props['class']);
        }

        $this->applyStyles($schema['styles'] ?? [], $props, $styles);
        $this->applyAttributes($schema['attributes'] ?? [], $props, $attr);

        $attr->add('class', $classes->get());

        $styleString = $styles->get();

        if ($styleString !== '') {
            $attr->add('style', $styleString);
        }

        if ($componentName) {
            $attr->add('data-component', $componentName);
        }

        return $attr->toArray();
    }

    /** @param array<string, mixed> $schema */
    public function renderStructure(array $schema, array $props, ?string $componentName = null): array
    {
        $this->contextProps = $props;
        $this->instanceIds = new InstanceIds($componentName ?? ($schema['name'] ?? 'component'), $props);
        $this->predeclareIds($schema);
        $built = [];

        foreach ($schema['children'] ?? [] as $key => $childSchema) {
            if (! $this->shouldRenderNode($childSchema, $props)) {
                continue;
            }

            $built[$key] = $this->renderNode($childSchema, $props, (string) $key, null);
        }

        return $built;
    }

    /** @param array<string, mixed> $schema */
    protected function renderNode(array $schema, array $props, string $key, ?string $parentPath = null): array
    {
        $path = $parentPath !== null ? "{$parentPath}.{$key}" : $key;

        $classes = new ClassFactory;
        $styles = new InlineStyleFactory;
        $attr = new AttributeFactory;

        $this->applyClassRules($schema['classRules'] ?? [], $props, $classes);
        $this->applyMatches($schema['matches'] ?? [], $props, $classes, $attr, $styles);
        $this->applyAttributes($schema['attributes'] ?? [], $props, $attr);
        $this->applyStyles($schema['styles'] ?? [], $props, $styles);

        $attributes = $attr->toArray();

        if ($styles->get() !== '') {
            $attributes['style'] = $styles->get();
        }

        if ($classes->get() !== '') {
            $attributes['class'] = $classes->get();
        }

        $node = [
            'key' => $key,
            'path' => $path,
            'tag' => ($schema['fragment'] ?? false) ? null : ($schema['tag'] ?? 'div'),
            'fragment' => (bool) ($schema['fragment'] ?? false),
            'attributes' => $attributes,
            'slot' => $schema['slot'] ?? null,
            'richText' => $schema['richText'] ?? null,
            'component' => $schema['component'] ?? null,
            'children' => [],
        ];

        foreach ($schema['children'] ?? [] as $childKey => $childSchema) {
            if ($this->shouldRenderNode($childSchema, $props)) {
                $node['children'][$childKey] = $this->renderNode($childSchema, $props, (string) $childKey, $path);
            }
        }

        return $node;
    }

    /** @param array<string, mixed> $schema */
    protected function shouldRenderNode(array $schema, array $props): bool
    {
        if (isset($schema['visible']) && ! $this->evaluateCondition($schema['visible'])) {
            return false;
        }

        if (isset($schema['hidden']) && $this->evaluateCondition($schema['hidden'])) {
            return false;
        }

        return true;
    }

    /** @param list<array<string, mixed>> $rules */
    protected function applyClassRules(array $rules, array $props, ClassFactory $classes): void
    {
        foreach ($rules as $rule) {
            if (! $this->evaluateCondition($rule['condition'] ?? null)) {
                continue;
            }

            $mode = $rule['mode'] ?? null;

            if ($mode === 'token') {
                $tokenClasses = config("sprout.tokens.{$rule['tokenGroup']}.{$rule['token']}");

                if (is_string($tokenClasses) && $tokenClasses !== '') {
                    $classes->apply($tokenClasses);
                }

                continue;
            }

            // Reserved / unknown modes (e.g. element, modifier) are no-ops until implemented.
            if ($mode !== null) {
                continue;
            }

            if (! empty($rule['classes'])) {
                $classes->apply($rule['classes']);
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $matches
     * @param  array<string, mixed>|null  $componentSchema
     */
    protected function applyMatches(
        array $matches,
        array $props,
        ClassFactory $classes,
        AttributeFactory $attr,
        InlineStyleFactory $styles,
        ?array $componentSchema = null,
    ): void {
        foreach ($matches as $match) {
            if (isset($match['preset'])) {
                $this->applyPresetMatch($match, $props, $classes);

                continue;
            }

            if (! $this->evaluateCondition($match['condition'] ?? null)) {
                continue;
            }

            $values = array_map(fn ($prop) => $this->lookupValue($props, $prop), $match['props'] ?? []);
            $matched = $this->findMatchCase($match['cases'] ?? [], $values);
            $outcomes = $matched ?? ($match['default'] ?? []);

            $this->applyOutcomes($outcomes, $classes, $attr, $styles);
        }
    }

    /** @param list<array<string, mixed>> $outcomes */
    protected function applyOutcomes(
        array $outcomes,
        ClassFactory $classes,
        AttributeFactory $attr,
        InlineStyleFactory $styles,
    ): void {
        foreach ($outcomes as $outcome) {
            $type = $outcome['type'] ?? null;

            match ($type) {
                'classes' => $classes->apply($outcome['value'] ?? ''),
                'attr' => $this->applyAttrOutcome($outcome, $attr),
                'style' => $this->applyStyleOutcome($outcome, $styles),
                null => null,
                default => $this->failLoud(
                    "Unknown match outcome type \"{$type}\".",
                    is_string($this->contextProps['__path'] ?? null) ? $this->contextProps['__path'] : null,
                ),
            };
        }
    }

    /** @param array<string, mixed> $outcome */
    protected function applyAttrOutcome(array $outcome, AttributeFactory $attr): void
    {
        if (! isset($outcome['name'])) {
            return;
        }

        $value = $outcome['value'] ?? null;

        if ($value === null || $value === false || $value === '') {
            return;
        }

        $attr->add(
            $outcome['name'],
            $this->resolveAttributeValue($value)
        );
    }

    /** @param array<string, mixed> $outcome */
    protected function applyStyleOutcome(array $outcome, InlineStyleFactory $styles): void
    {
        if (! isset($outcome['property'])) {
            return;
        }

        $value = $outcome['value'] ?? null;

        if ($value === null || $value === false || $value === '') {
            return;
        }

        $styles->add($outcome['property'], (string) $value);
    }

    protected function failLoud(string $message, ?string $path = null): void
    {
        if (! $this->isDebug()) {
            return;
        }

        throw new SchemaException(
            $message,
            is_string($this->contextProps['__component'] ?? null) ? $this->contextProps['__component'] : null,
            $path,
        );
    }

    protected function isDebug(): bool
    {
        try {
            return (bool) config('app.debug', false) || (bool) config('sprout.editor.debug', false);
        } catch (\Throwable) {
            return false;
        }
    }

    /** @param list<array<string, mixed>> $cases */
    protected function findMatchCase(array $cases, array $values): ?array
    {
        foreach ($cases as $case) {
            $caseValues = $case['values'] ?? [];
            $match = true;

            foreach ($values as $index => $value) {
                $normalized = $this->normalizeLookupValue($value);
                $caseValue = $caseValues[$index] ?? null;

                if (! $this->matchCaseValue($normalized, $caseValue)) {
                    $match = false;
                    break;
                }
            }

            if ($match) {
                return $case['outcomes'] ?? [];
            }
        }

        return null;
    }

    protected function matchCaseValue(string $normalized, mixed $caseValue): bool
    {
        $case = (string) $caseValue;

        if ($normalized === $case) {
            return true;
        }

        if ($case === 'true' && in_array($normalized, ['true', '1'], true)) {
            return true;
        }

        if ($case === 'false' && in_array($normalized, ['false', '0', ''], true)) {
            return true;
        }

        // Null/empty optional enum props match an explicit "default" case label.
        if ($normalized === '' && $case === 'default') {
            return true;
        }

        return false;
    }

    protected function applyPresetMatch(array $match, array $props, ClassFactory $classes): void
    {
        if (! $this->evaluateCondition($match['condition'] ?? null)) {
            return;
        }

        $prop = $match['props'][0] ?? $match['preset'];
        $value = $this->lookupValue($props, $prop);
        $map = config("sprout.presets.{$match['preset']}", []);

        if (! is_array($map)) {
            return;
        }

        if (isset($map['base'], $map['responsive']) && is_array($map['base'])) {
            $normalized = $this->normalizeLookupValue($value);

            if (isset($map['base'][$normalized])) {
                $classes->apply($map['base'][$normalized]);
            }

            foreach ($map['responsive'] as $responsiveProp => $breakpoint) {
                $responsiveValue = $this->normalizeLookupValue($this->lookupValue($props, $responsiveProp));

                if (isset($map[$breakpoint][$responsiveValue])) {
                    $classes->apply($map[$breakpoint][$responsiveValue]);
                }
            }

            return;
        }

        $normalized = $this->normalizeLookupValue($value);

        $this->applyPresetMapEntry($match['preset'], $normalized, $classes);
    }

    protected function applyPresetMapEntry(string $presetKey, string $normalized, ClassFactory $classes): void
    {
        $map = config("sprout.presets.{$presetKey}", []);

        if (! is_array($map)) {
            return;
        }

        if (isset($map[$normalized])) {
            $classes->apply($map[$normalized]);
        }

        $nestedKey = "{$presetKey}Nested";
        $nestedMap = config("sprout.presets.{$nestedKey}", []);

        if (is_array($nestedMap) && isset($nestedMap[$normalized])) {
            $classes->apply($nestedMap[$normalized]);
        }
    }

    /** @param list<array<string, mixed>> $attributes */
    protected function applyAttributes(array $attributes, array $props, AttributeFactory $attr): void
    {
        foreach ($attributes as $definition) {
            if (! $this->evaluateCondition($definition['condition'] ?? null)) {
                continue;
            }

            if (! empty($definition['uniqueId'])) {
                $attr->add($definition['name'] ?? 'id', $this->ids()->declare((string) $definition['uniqueId']));

                continue;
            }

            if (! empty($definition['idRef'])) {
                $attr->add($definition['name'], $this->ids()->declare((string) $definition['idRef']));

                continue;
            }

            if (($definition['source'] ?? null) === null) {
                if (($definition['value'] ?? null) !== null && $definition['value'] !== false) {
                    $attr->add(
                        $definition['name'],
                        $this->resolveAttributeValue($definition['value'])
                    );
                }

                continue;
            }

            $value = $this->lookupValue($props, $definition['source']);

            if (($value === null || $value === false || $value === '') && array_key_exists('default', $definition)) {
                $value = $definition['default'];
            }

            if ($value === null || $value === false || $value === '') {
                continue;
            }

            $casted = $this->transforms->cast($definition['cast'] ?? 'string', $value);

            $attr->add(
                $definition['name'],
                $this->resolveAttributeValue($casted)
            );
        }
    }

    protected function resolveAttributeValue(mixed $value): mixed
    {
        if (! IdInterpolator::shouldInterpolate($value)) {
            return $value;
        }

        return IdInterpolator::interpolate(
            (string) $value,
            $this->ids(),
            $this->isDebug(),
            is_string($this->contextProps['__component'] ?? null) ? $this->contextProps['__component'] : null,
        );
    }

    protected function ids(): InstanceIds
    {
        return $this->instanceIds ??= new InstanceIds('component', $this->contextProps);
    }

    /** @param array<string, mixed> $schema */
    protected function predeclareIds(array $schema): void
    {
        foreach ($schema['attributes'] ?? [] as $definition) {
            if (! empty($definition['uniqueId'])) {
                $this->ids()->declare((string) $definition['uniqueId']);
            }

            if (! empty($definition['idRef'])) {
                $this->ids()->declare((string) $definition['idRef']);
            }
        }

        foreach ($schema['children'] ?? [] as $child) {
            if (is_array($child)) {
                $this->predeclareIds($child);
            }
        }
    }

    /** @param list<array<string, mixed>> $styles */
    protected function applyStyles(array $styles, array $props, InlineStyleFactory $stylesFactory): void
    {
        foreach ($styles as $definition) {
            if (! $this->evaluateCondition($definition['condition'] ?? null)) {
                continue;
            }

            $value = $this->lookupValue($props, $definition['source'] ?? '');

            if (($value === null || $value === false || $value === '') && array_key_exists('default', $definition)) {
                $value = $definition['default'];
            }

            if ($value === null || $value === false || $value === '') {
                continue;
            }

            $cast = $definition['cast'] ?? 'string';
            $resolved = $this->transforms->cast($cast, $value);

            if ($definition['cssUrl'] ?? false) {
                $resolved = $this->transforms->cast('cssUrl', $resolved);
            }

            $stylesFactory->add(
                $definition['property'],
                (string) $resolved
            );
        }
    }

    protected function resolveConditionValue(string $prop): mixed
    {
        return $this->lookupValue($this->contextProps, $prop);
    }

    protected function lookupValue(array $props, string $key): mixed
    {
        $parts = explode('.', $key);
        $value = $props;

        foreach ($parts as $part) {
            if (is_array($value) && array_key_exists($part, $value)) {
                $value = $value[$part];
            } elseif (is_object($value) && isset($value->{$part})) {
                $value = $value->{$part};
            } else {
                return null;
            }
        }

        return $value;
    }

    protected function normalizeLookupValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            return $trimmed === '' ? '' : $trimmed;
        }

        if ($value === null) {
            return '';
        }

        return (string) $value;
    }
}
