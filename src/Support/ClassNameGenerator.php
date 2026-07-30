<?php

namespace Parity\Support;

/**
 * Generates BEM / kebab / state class tokens from schema naming rules.
 */
final class ClassNameGenerator
{
    /** @var array<string, mixed> */
    private array $bem;

    /** @var array<string, mixed> */
    private array $variant;

    /** @var array<string, mixed> */
    private array $state;

    /**
     * @param  array<string, mixed>|null  $bem
     * @param  array<string, mixed>|null  $variant
     * @param  array<string, mixed>|null  $state
     */
    public function __construct(?array $bem = null, ?array $variant = null, ?array $state = null)
    {
        $this->bem = $bem ?? self::defaultBem();
        $this->variant = $variant ?? self::defaultVariant();
        $this->state = $state ?? self::defaultState();
    }

    public static function fromConfig(): self
    {
        try {
            return new self(
                is_array(config('parity.bem')) ? config('parity.bem') : null,
                is_array(config('parity.variant')) ? config('parity.variant') : null,
                is_array(config('parity.state')) ? config('parity.state') : null,
            );
        } catch (\Throwable) {
            return new self;
        }
    }

    /** @return array<string, mixed> */
    public static function defaultBem(): array
    {
        return [
            'categories' => [
                'component' => 'c-',
                'object' => 'o-',
                'organizer' => 'o-',
                'module' => 'm-',
                'utility' => 'u-',
            ],
            'element' => '__',
            'modifier' => '--',
            'breakpoint' => '@',
        ];
    }

    /** @return array<string, mixed> */
    public static function defaultVariant(): array
    {
        return [
            'element' => '-',
            'join' => '-',
            'format' => 'kebab',
        ];
    }

    /** @return array<string, mixed> */
    public static function defaultState(): array
    {
        return [
            'is' => 'is-',
            'has' => 'has-',
        ];
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    public function resolveBlock(array $schema): string
    {
        $name = (string) ($schema['block'] ?? $schema['name'] ?? '');
        $prefix = $this->categoryPrefix($schema['category'] ?? null);

        return $prefix.$name;
    }

    public function categoryPrefix(mixed $category): string
    {
        if (! is_string($category) || $category === '') {
            return '';
        }

        $normalized = strtolower(rtrim($category, 's'));
        $aliases = [
            'component' => 'component',
            'object' => 'object',
            'organizer' => 'organizer',
            'module' => 'module',
            'utilitie' => 'utility',
            'utility' => 'utility',
        ];

        // "utilities" → utilitie after rtrim s; map both.
        if ($normalized === 'utilitie') {
            $normalized = 'utility';
        }

        $key = $aliases[$normalized] ?? $normalized;
        $categories = $this->bem['categories'] ?? [];

        if (! is_array($categories)) {
            return '';
        }

        // Also try plural forms as configured keys.
        foreach ([$key, $key.'s', strtolower((string) $category)] as $candidate) {
            if (isset($categories[$candidate]) && is_string($categories[$candidate])) {
                return $categories[$candidate];
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $schema
     */
    public function shouldEmitBlock(array $schema): bool
    {
        if (! empty($schema['category']) || ! empty($schema['block'])) {
            return true;
        }

        return $this->rulesUseNaming($schema['classRules'] ?? [])
            || $this->childrenUseNaming($schema['children'] ?? []);
    }

    /**
     * @param  list<array<string, mixed>>  $rules
     */
    public function rulesUseNaming(array $rules): bool
    {
        foreach ($rules as $rule) {
            $mode = $rule['mode'] ?? null;

            if (in_array($mode, ['element', 'modifier', 'variant', 'state'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $children
     */
    protected function childrenUseNaming(array $children): bool
    {
        foreach ($children as $child) {
            if (! is_array($child)) {
                continue;
            }

            if ($this->rulesUseNaming($child['classRules'] ?? [])) {
                return true;
            }

            if ($this->childrenUseNaming($child['children'] ?? [])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array<string, mixed>>  $rules
     * @return 'bem'|'variant'
     */
    public function namingStyle(array $rules, bool $hasCategory): string
    {
        $hasModifier = false;
        $hasVariant = false;

        foreach ($rules as $rule) {
            $mode = $rule['mode'] ?? null;

            if ($mode === 'modifier') {
                $hasModifier = true;
            }

            if ($mode === 'variant') {
                $hasVariant = true;
            }
        }

        if ($hasModifier) {
            return 'bem';
        }

        if ($hasVariant) {
            return 'variant';
        }

        return $hasCategory ? 'bem' : 'variant';
    }

    public function elementClass(string $block, string $element, string $style): string
    {
        $sep = $style === 'bem'
            ? (string) ($this->bem['element'] ?? '__')
            : (string) ($this->variant['element'] ?? '-');

        return $block.$sep.$this->formatSegment($element);
    }

    /**
     * @param  array<string, mixed>  $rule
     * @param  array<string, mixed>  $props
     */
    public function modifierClass(string $base, array $rule, array $props): ?string
    {
        $as = $rule['as'] ?? null;
        $breakpoint = isset($rule['breakpoint']) && is_string($rule['breakpoint']) && $rule['breakpoint'] !== ''
            ? $rule['breakpoint']
            : null;

        if (array_key_exists('value', $rule) && $rule['value'] !== null) {
            $key = is_string($as) && $as !== '' ? $as : 'mod';
            $formatted = $this->formatSegment((string) $rule['value']);

            return $this->bemModifierToken($base, $key, $formatted, $breakpoint, boolean: false);
        }

        $sources = $this->normalizeSources($rule['source'] ?? $as ?? null);

        if ($sources === []) {
            return null;
        }

        $key = is_string($as) && $as !== ''
            ? $as
            : (count($sources) === 1 ? $sources[0] : 'mod');

        if (count($sources) === 1) {
            $resolved = $this->resolvePropValue($props, $sources[0], $breakpoint);

            if ($this->isOmitValue($resolved)) {
                return null;
            }

            if (is_bool($resolved)) {
                return $resolved
                    ? $this->bemModifierToken($base, $key, null, $breakpoint, boolean: true)
                    : null;
            }

            return $this->bemModifierToken($base, $key, $this->formatSegment($resolved), $breakpoint, boolean: false);
        }

        $parts = [];

        foreach ($sources as $source) {
            $resolved = $this->resolvePropValue($props, $source, $breakpoint);

            if ($this->isOmitValue($resolved) || is_bool($resolved)) {
                return null;
            }

            $parts[] = $this->formatSegment($resolved);
        }

        return $this->bemModifierToken($base, $key, implode('-', $parts), $breakpoint, boolean: false);
    }

    /**
     * @param  array<string, mixed>  $rule
     * @param  array<string, mixed>  $props
     */
    public function variantClass(string $base, array $rule, array $props): ?string
    {
        $breakpoint = isset($rule['breakpoint']) && is_string($rule['breakpoint']) && $rule['breakpoint'] !== ''
            ? $rule['breakpoint']
            : null;
        $join = (string) ($this->variant['join'] ?? '-');

        if (array_key_exists('value', $rule) && $rule['value'] !== null) {
            $formatted = $this->formatSegment((string) $rule['value']);

            return $this->kebabToken($base, $formatted, $breakpoint);
        }

        $sources = $this->normalizeSources($rule['source'] ?? null);

        if ($sources === []) {
            return null;
        }

        if (count($sources) === 1) {
            $key = $sources[0];
            $resolved = $this->resolvePropValue($props, $key, $breakpoint);

            if ($this->isOmitValue($resolved)) {
                return null;
            }

            if (is_bool($resolved)) {
                return $resolved
                    ? $this->kebabToken($base, $this->formatSegment($key), $breakpoint)
                    : null;
            }

            return $this->kebabToken($base, $this->formatSegment($resolved), $breakpoint);
        }

        $parts = [];

        foreach ($sources as $source) {
            $resolved = $this->resolvePropValue($props, $source, $breakpoint);

            if ($this->isOmitValue($resolved) || is_bool($resolved)) {
                return null;
            }

            $parts[] = $this->formatSegment($resolved);
        }

        return $this->kebabToken($base, implode($join, $parts), $breakpoint);
    }

    /**
     * @param  array<string, mixed>  $rule
     * @param  array<string, mixed>  $props
     */
    public function stateClass(array $rule, array $props): ?string
    {
        $kind = $rule['state'] ?? 'is';
        $name = (string) ($rule['stateName'] ?? $rule['source'] ?? '');
        $source = $rule['source'] ?? $name;

        if ($name === '' || ! is_string($source)) {
            return null;
        }

        $resolved = $this->lookup($props, $source);

        if (! $resolved) {
            return null;
        }

        $prefix = (string) ($this->state[$kind] ?? ($kind === 'has' ? 'has-' : 'is-'));

        return $prefix.$this->formatSegment($name);
    }

    protected function bemModifierToken(string $base, string $key, ?string $value, ?string $breakpoint, bool $boolean): string
    {
        $mod = (string) ($this->bem['modifier'] ?? '--');
        $bpSep = (string) ($this->bem['breakpoint'] ?? '@');
        $keySeg = $this->formatSegment($key);

        $stem = $base;

        if ($breakpoint !== null) {
            $stem .= $bpSep.$this->formatSegment($breakpoint);
        }

        if ($boolean || $value === null || $value === '') {
            return $stem.$mod.$keySeg;
        }

        return $stem.$mod.$keySeg.'-'.$value;
    }

    protected function kebabToken(string $base, string $segment, ?string $breakpoint): string
    {
        $join = (string) ($this->variant['join'] ?? '-');

        if ($breakpoint !== null) {
            return $base.$join.$this->formatSegment($breakpoint).$join.$segment;
        }

        return $base.$join.$segment;
    }

    /**
     * @return list<string>
     */
    protected function normalizeSources(mixed $source): array
    {
        if (is_string($source) && $source !== '') {
            return [$source];
        }

        if (! is_array($source)) {
            return [];
        }

        $out = [];

        foreach ($source as $item) {
            if (is_string($item) && $item !== '') {
                $out[] = $item;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $props
     */
    public function resolvePropValue(array $props, string $source, ?string $breakpoint): mixed
    {
        if ($breakpoint !== null) {
            $studly = $source.ucfirst($breakpoint);
            $underscored = $source.'_'.$breakpoint;

            foreach ([$studly, $underscored] as $candidate) {
                if ($this->hasProp($props, $candidate)) {
                    return $this->lookup($props, $candidate);
                }
            }
        }

        if ($this->hasProp($props, $source)) {
            return $this->lookup($props, $source);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $props
     */
    protected function hasProp(array $props, string $key): bool
    {
        if (! str_contains($key, '.')) {
            return array_key_exists($key, $props);
        }

        return $this->lookup($props, $key) !== null || $this->pathExists($props, $key);
    }

    /**
     * @param  array<string, mixed>  $props
     */
    protected function pathExists(array $props, string $key): bool
    {
        $parts = explode('.', $key);
        $value = $props;

        foreach ($parts as $part) {
            if (! is_array($value) || ! array_key_exists($part, $value)) {
                return false;
            }

            $value = $value[$part];
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $props
     */
    protected function lookup(array $props, string $key): mixed
    {
        if (! str_contains($key, '.')) {
            return $props[$key] ?? null;
        }

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

    protected function isOmitValue(mixed $value): bool
    {
        return $value === null || $value === false || $value === '';
    }

    public function formatSegment(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $string = trim((string) $value);
        $format = (string) ($this->variant['format'] ?? 'kebab');

        if ($format === 'raw') {
            return $string;
        }

        $string = strtolower($string);
        $string = preg_replace('/[^a-z0-9]+/', '-', $string) ?? $string;

        return trim($string, '-');
    }
}
