<?php

namespace Sprout\Render;

use Sprout\Concerns\EvaluatesConditions;
use Sprout\Registries\TransformRegistry;
use Sprout\Support\AttributeFactory;
use Sprout\Support\ClassFactory;
use Sprout\Support\InlineStyleFactory;

class SchemaRenderer
{
    use EvaluatesConditions;

    /** @var array<string, mixed> */
    protected array $contextProps = [];

    public function __construct(
        protected TransformRegistry $transforms,
    ) {}

    /** @param array<string, mixed> $schema */
    public function renderComponentAttributes(array $schema, array $props, ?string $componentName = null): array
    {
        $this->contextProps = $props;
        $classes = new ClassFactory;
        $this->applyClassRules($schema['classRules'] ?? [], $props, $classes);
        $this->applyMatches($schema['matches'] ?? [], $props, $classes, $schema);

        if (! empty($props['class'])) {
            $classes->apply((string) $props['class']);
        }

        $styles = new InlineStyleFactory;
        $this->applyStyles($schema['styles'] ?? [], $props, $styles);

        $attr = new AttributeFactory;
        $attr->add('class', $classes->get());

        $styleString = $styles->get();

        if ($styleString !== '') {
            $attr->add('style', $styleString);
        }

        if ($componentName) {
            $attr->add('data-component', $componentName);
        }

        $this->applyAttributes($schema['attributes'] ?? [], $props, $attr);

        return $attr->toArray();
    }

    /** @param array<string, mixed> $schema */
    public function renderStructure(array $schema, array $props): array
    {
        $this->contextProps = $props;
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
        $this->applyClassRules($schema['classRules'] ?? [], $props, $classes);
        $this->applyMatches($schema['matches'] ?? [], $props, $classes);

        $attributes = [];
        $this->applyAttributesToArray($schema['attributes'] ?? [], $props, $attributes);

        $styles = new InlineStyleFactory;
        $this->applyStyles($schema['styles'] ?? [], $props, $styles);

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
            'componentRef' => $schema['componentRef'] ?? null,
            'componentProps' => $schema['componentProps'] ?? [],
            'componentMapping' => $schema['componentMapping'] ?? null,
            'componentMappingKey' => $schema['componentMappingKey'] ?? null,
            'componentClass' => $schema['componentClass'] ?? null,
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

            if (($rule['mode'] ?? null) === 'token') {
                $tokenClasses = config("sprout.tokens.{$rule['tokenGroup']}.{$rule['token']}");

                if (is_string($tokenClasses) && $tokenClasses !== '') {
                    $classes->apply($tokenClasses);
                }

                continue;
            }

            if (! empty($rule['classes'])) {
                $classes->apply($rule['classes']);
            }
        }
    }

    /** @param list<array<string, mixed>> $matches */
    protected function applyMatches(array $matches, array $props, ClassFactory $classes, ?array $componentSchema = null): void
    {
        foreach ($matches as $match) {
            if (isset($match['common'])) {
                $this->applyCommonMatch($match, $props, $classes);

                continue;
            }

            if (! $this->evaluateCondition($match['condition'] ?? null)) {
                continue;
            }

            $values = array_map(fn ($prop) => $this->lookupValue($props, $prop), $match['props'] ?? []);
            $matched = $this->findMatchCase($match['cases'] ?? [], $values);
            $outcomes = $matched ?? ($match['default'] ?? []);

            $this->applyOutcomes($outcomes, $props, $classes);
        }
    }

    /** @param list<array<string, mixed>> $outcomes */
    protected function applyOutcomes(array $outcomes, array $props, ClassFactory $classes): void
    {
        foreach ($outcomes as $outcome) {
            match ($outcome['type']) {
                'classes' => $classes->apply($outcome['value'] ?? ''),
                default => null,
            };
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

    protected function applyCommonMatch(array $match, array $props, ClassFactory $classes): void
    {
        if (! $this->evaluateCondition($match['condition'] ?? null)) {
            return;
        }

        $prop = $match['props'][0] ?? $match['common'];
        $value = $this->lookupValue($props, $prop);
        $map = config("sprout.common.{$match['common']}", []);

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

        $this->applyCommonMapEntry($match['common'], $normalized, $classes);
    }

    protected function applyCommonMapEntry(string $commonKey, string $normalized, ClassFactory $classes): void
    {
        $map = config("sprout.common.{$commonKey}", []);

        if (! is_array($map)) {
            return;
        }

        if (isset($map[$normalized])) {
            $classes->apply($map[$normalized]);
        }

        $nestedKey = "{$commonKey}Nested";
        $nestedMap = config("sprout.common.{$nestedKey}", []);

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

            if ($definition['source'] === null) {
                if ($definition['value'] !== null && $definition['value'] !== false) {
                    $attr->add($definition['name'], $definition['value']);
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

            $attr->add(
                $definition['name'],
                $this->transforms->cast($definition['cast'] ?? 'string', $value)
            );
        }
    }

    /** @param list<array<string, mixed>> $attributes */
    protected function applyAttributesToArray(array $attributes, array $props, array &$target): void
    {
        foreach ($attributes as $definition) {
            if (! $this->evaluateCondition($definition['condition'] ?? null)) {
                continue;
            }

            if ($definition['source'] === null) {
                if ($definition['value'] !== null && $definition['value'] !== false) {
                    $target[$definition['name']] = $definition['value'];
                }

                continue;
            }

            $value = $this->lookupValue($props, $definition['source']);

            if ($value === null || $value === false || $value === '') {
                continue;
            }

            $target[$definition['name']] = $this->transforms->cast($definition['cast'] ?? 'string', $value);
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
