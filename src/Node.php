<?php

namespace Sprout;

use Sprout\Builders\AttrBuilder;
use Sprout\Builders\ConditionBuilder;
use Sprout\Builders\MatchBuilder;
use Sprout\Builders\StyleBuilder;
use Sprout\Schema\Version;

class Node
{
    protected ?string $key = null;

    protected ?string $tag = 'div';

    protected bool $fragment = false;

    protected ?string $slotName = null;

    protected bool $isDefaultSlot = false;

    /** @var list<array<string, mixed>> */
    protected array $classRules = [];

    /** @var list<array<string, mixed>> */
    protected array $matches = [];

    /** @var list<array<string, mixed>> */
    protected array $attributes = [];

    /** @var list<array<string, mixed>> */
    protected array $styles = [];

    protected ?array $visible = null;

    protected ?array $hidden = null;

    protected ?array $richText = null;

    protected ?string $componentRef = null;

    /** @var array<string, mixed> */
    protected array $componentProps = [];

    protected ?string $componentMappingKey = null;

    /** @var array<string, string> */
    protected array $componentMapping = [];

    protected ?string $componentClass = null;

    /** @var list<Node> */
    protected array $childrenNodes = [];

    public static function make(string $key, ?string $tag = 'div'): static
    {
        $node = new static;
        $node->key = $key;
        $node->tag = $tag;

        return $node;
    }

    public static function namedSlot(string $name): static
    {
        $node = new static;
        $node->key = $name;
        $node->slotName = $name;
        $node->isDefaultSlot = false;
        $node->fragment = true;

        return $node;
    }

    public function fragment(bool $enabled = true): static
    {
        $this->fragment = $enabled;

        if ($enabled) {
            $this->tag = null;
        }

        return $this;
    }

    public function classes(string $classes): static
    {
        return $this->pushClassRule($classes);
    }

    public function when(string $prop, mixed $value = null): static
    {
        $lastIndex = count($this->classRules) - 1;

        if ($lastIndex < 0) {
            throw new \RuntimeException('Call classes() before when().');
        }

        $this->classRules[$lastIndex]['condition'] = $value === null
            ? ConditionBuilder::truthy($prop)->toArray()
            : ConditionBuilder::equals($prop, $value)->toArray();

        return $this;
    }

    public function unless(string $prop, mixed $value = null): static
    {
        $lastIndex = count($this->classRules) - 1;

        if ($lastIndex < 0) {
            throw new \RuntimeException('Call classes() before unless().');
        }

        $this->classRules[$lastIndex]['condition'] = $value === null
            ? ['prop' => $prop, 'operator' => 'falsy']
            : ConditionBuilder::notEquals($prop, $value)->toArray();

        return $this;
    }

    public function apply(string $tokenGroup, string $token): static
    {
        return $this->pushClassRule('', mode: 'token', tokenGroup: $tokenGroup, token: $token);
    }

    public function match(string ...$props): MatchBuilder
    {
        return new MatchBuilder($this, $props);
    }

    public function attr(string $name, mixed $value = null): AttrBuilder
    {
        return new AttrBuilder($this, $name, $value);
    }

    public function attrs(array $attributes): static
    {
        foreach ($attributes as $name => $value) {
            $this->pushAttribute([
                'name' => $name,
                'value' => $value,
                'source' => null,
                'cast' => 'string',
                'default' => null,
                'condition' => null,
            ]);
        }

        return $this;
    }

    public function style(string $property): StyleBuilder
    {
        return new StyleBuilder($this, $property);
    }

    public function visible(string $prop): static
    {
        $this->visible = ConditionBuilder::truthy($prop)->toArray();

        return $this;
    }

    public function hidden(string $prop): static
    {
        $this->hidden = ConditionBuilder::truthy($prop)->toArray();

        return $this;
    }

    public function richText(string $prop, ?string $placeholder = null, array $allowedFormats = []): static
    {
        $this->richText = [
            'prop' => $prop,
            'placeholder' => $placeholder,
            'allowedFormats' => $allowedFormats,
        ];

        return $this;
    }

    public function component(string $name, array $props = []): static
    {
        $this->componentRef = $name;
        $this->componentProps = $props;

        return $this;
    }

    public function mappedComponent(string $component, string $prop, array $map, ?string $class = null): static
    {
        $this->componentRef = $component;
        $this->componentMappingKey = $prop;
        $this->componentMapping = $map;
        $this->componentClass = $class;

        return $this;
    }

    public function holdsDefaultSlot(): static
    {
        $this->isDefaultSlot = true;
        $this->slotName = null;

        return $this;
    }

    public function holdsNamedSlot(string $name): static
    {
        $this->slotName = $name;
        $this->isDefaultSlot = false;

        return $this;
    }

    /** @param list<Node> $children */
    public function children(array $children): static
    {
        $this->childrenNodes = $children;

        return $this;
    }

    public function includeCommon(string $commonKey, ?string $as = null, ?array $condition = null): static
    {
        $this->matches[] = [
            'props' => [$as ?? $commonKey],
            'common' => $commonKey,
            'condition' => $condition,
        ];

        return $this;
    }

    public function toSchema(): array
    {
        $schema = [
            'schemaVersion' => Version::CURRENT,
            'key' => $this->key,
            'fragment' => $this->fragment,
            'tag' => $this->tag,
            'classRules' => $this->classRules,
            'matches' => $this->matches,
            'attributes' => $this->attributes,
            'styles' => $this->styles,
        ];

        if ($this->slotName !== null || $this->isDefaultSlot) {
            $schema['slot'] = [
                'name' => $this->slotName,
                'default' => $this->isDefaultSlot,
            ];
        }

        if ($this->visible !== null) {
            $schema['visible'] = $this->visible;
        }

        if ($this->hidden !== null) {
            $schema['hidden'] = $this->hidden;
        }

        if ($this->richText !== null) {
            $schema['richText'] = $this->richText;
        }

        if ($this->componentRef !== null) {
            $schema['componentRef'] = $this->componentRef;
            $schema['componentProps'] = $this->componentProps;

            if ($this->componentMappingKey !== null) {
                $schema['componentMappingKey'] = $this->componentMappingKey;
                $schema['componentMapping'] = $this->componentMapping;
            }

            if ($this->componentClass !== null) {
                $schema['componentClass'] = $this->componentClass;
            }
        }

        if ($this->childrenNodes !== []) {
            $schema['children'] = [];

            foreach ($this->childrenNodes as $child) {
                $childSchema = $child->toSchema();
                $schema['children'][$childSchema['key']] = $childSchema;
            }
        }

        return $schema;
    }

    protected function pushClassRule(
        string $classes,
        ?string $mode = null,
        ?string $tokenGroup = null,
        ?string $token = null,
    ): static {
        $rule = ['classes' => $classes, 'condition' => null];

        if ($mode === 'token') {
            $rule['mode'] = 'token';
            $rule['tokenGroup'] = $tokenGroup;
            $rule['token'] = $token;
        }

        $this->classRules[] = $rule;

        return $this;
    }

    public function pushMatch(array $match): void
    {
        $this->matches[] = $match;
    }

    public function pushAttribute(array $attribute): void
    {
        $this->attributes[] = $attribute;
    }

    public function pushStyle(array $style): void
    {
        $this->styles[] = $style;
    }
}
