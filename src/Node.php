<?php

namespace Sprout;

use Sprout\Builders\AttrBuilder;
use Sprout\Builders\ConditionBuilder;
use Sprout\Builders\EmbedBuilder;
use Sprout\Builders\MatchBuilder;
use Sprout\Builders\StyleBuilder;
use Sprout\Exceptions\SchemaException;
use Sprout\Schema\Version;

/**
 * Fluent schema node builder.
 *
 * @method static static make(string $key, ?string $tag = 'div')
 */
class Node
{
    protected ?string $key = null;

    protected ?string $tag = 'div';

    protected bool $isFragment = false;

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

    protected ?array $visibleWhen = null;

    protected ?array $hiddenWhen = null;

    protected ?array $richTextConfig = null;

    /** @var array<string, mixed>|null */
    protected ?array $componentConfig = null;

    /** @var list<Node> */
    protected array $childrenNodes = [];

    /** @var list<string> */
    protected array $openBuilders = [];

    public static function make(string $key, ?string $tag = 'div'): static
    {
        $node = new static;
        $node->key = $key;
        $node->tag = $tag;

        return $node;
    }

    public function registerOpenBuilder(string $name): void
    {
        $this->openBuilders[] = $name;
    }

    public function clearOpenBuilder(string $name): void
    {
        $index = array_search($name, $this->openBuilders, true);

        if ($index !== false) {
            array_splice($this->openBuilders, $index, 1);
        }
    }

    /**
     * @return $this
     */
    public function fragment(bool $enabled = true): static
    {
        $this->isFragment = $enabled;

        if ($enabled) {
            $this->tag = null;
        }

        return $this;
    }

    /**
     * @return $this
     */
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

    public function token(string $group, string $name): static
    {
        return $this->pushClassRule('', mode: 'token', tokenGroup: $group, token: $name);
    }

    public function match(string ...$props): MatchBuilder
    {
        return new MatchBuilder($this, $props);
    }

    public function attr(string $name, mixed $value = null): AttrBuilder
    {
        return new AttrBuilder($this, $name, $value);
    }

    public function uniqueId(string $name): static
    {
        $this->pushAttribute([
            'name' => 'id',
            'value' => null,
            'source' => null,
            'cast' => 'string',
            'default' => null,
            'uniqueId' => $name,
            'idRef' => null,
            'condition' => null,
        ]);

        return $this;
    }

    public function xData(string $expression): static
    {
        return $this->alpineAttr('x-data', $expression);
    }

    public function xInit(string $expression): static
    {
        return $this->alpineAttr('x-init', $expression);
    }

    public function xShow(string $expression): static
    {
        return $this->alpineAttr('x-show', $expression);
    }

    public function xCloak(): static
    {
        return $this->alpineAttr('x-cloak', true);
    }

    public function xOn(string $event, string $expression): static
    {
        return $this->alpineAttr('x-on:'.$event, $expression);
    }

    public function xBind(string $attribute, string $expression): static
    {
        return $this->alpineAttr('x-bind:'.$attribute, $expression);
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
                'uniqueId' => null,
                'idRef' => null,
                'condition' => null,
            ]);
        }

        return $this;
    }

    protected function alpineAttr(string $name, mixed $value): static
    {
        $this->pushAttribute([
            'name' => $name,
            'value' => $value,
            'source' => null,
            'cast' => 'string',
            'default' => null,
            'uniqueId' => null,
            'idRef' => null,
            'condition' => null,
        ]);

        return $this;
    }

    public function style(string $property): StyleBuilder
    {
        return new StyleBuilder($this, $property);
    }

    public function visible(string $prop): static
    {
        $this->visibleWhen = ConditionBuilder::truthy($prop)->toArray();

        return $this;
    }

    public function hidden(string $prop): static
    {
        $this->hiddenWhen = ConditionBuilder::truthy($prop)->toArray();

        return $this;
    }

    public function richText(string $prop, ?string $placeholder = null, array $allowedFormats = []): static
    {
        $this->richTextConfig = [
            'prop' => $prop,
            'placeholder' => $placeholder,
            'allowedFormats' => $allowedFormats,
        ];

        return $this;
    }

    /**
     * Mark this node as a slot holder.
     *
     * @param  string|null  $name  Null for the default slot; a string for a named slot.
     * @return $this
     */
    public function slot(?string $name = null): static
    {
        if ($name === null) {
            $this->isDefaultSlot = true;
            $this->slotName = null;
        } else {
            $this->slotName = $name;
            $this->isDefaultSlot = false;
        }

        return $this;
    }

    /**
     * Embed a nested component (static ref or prop-mapped).
     */
    public function component(?string $ref = null): EmbedBuilder
    {
        return new EmbedBuilder($this, $ref);
    }

    /**
     * @param  array<string, mixed>  $component
     */
    public function setComponent(array $component): void
    {
        $this->componentConfig = $component;
    }

    /** @param list<Node> $children */
    public function children(array $children): static
    {
        $this->childrenNodes = $children;

        return $this;
    }

    public function preset(string $name, ?string $as = null, ?array $condition = null): static
    {
        $this->matches[] = [
            'props' => [$as ?? $name],
            'preset' => $name,
            'condition' => $condition,
        ];

        return $this;
    }

    public function toSchema(): array
    {
        if ($this->openBuilders !== []) {
            throw new SchemaException(
                'Unclosed builder(s): '.implode(', ', array_unique($this->openBuilders)).'. Call end() before toSchema().'
            );
        }

        $schema = [
            'schemaVersion' => Version::CURRENT,
            'key' => $this->key,
            'fragment' => $this->isFragment,
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

        if ($this->visibleWhen !== null) {
            $schema['visible'] = $this->visibleWhen;
        }

        if ($this->hiddenWhen !== null) {
            $schema['hidden'] = $this->hiddenWhen;
        }

        if ($this->richTextConfig !== null) {
            $schema['richText'] = $this->richTextConfig;
        }

        if ($this->componentConfig !== null) {
            $component = [];

            if ($this->componentConfig['ref'] !== null) {
                $component['ref'] = $this->componentConfig['ref'];
            }

            if ($this->componentConfig['from'] !== null) {
                $component['from'] = $this->componentConfig['from'];
                $component['map'] = $this->componentConfig['map'];
            }

            if ($this->componentConfig['class'] !== null) {
                $component['class'] = $this->componentConfig['class'];
            }

            if ($this->componentConfig['props'] !== []) {
                $component['props'] = $this->componentConfig['props'];
            }

            if ($component !== []) {
                $schema['component'] = $component;
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
