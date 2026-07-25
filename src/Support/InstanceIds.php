<?php

namespace Sprout\Support;

final class InstanceIds
{
    /** @var array<string, string> */
    protected array $ids = [];

    /** @var array<string, true> */
    protected array $declared = [];

    protected string $instanceKey;

    /**
     * @param  array<string, mixed>  $props
     */
    public function __construct(
        protected string $componentName,
        array $props = [],
    ) {
        $this->instanceKey = self::resolveInstanceKey($componentName, $props);
    }

    /**
     * @param  array<string, mixed>  $props
     */
    public static function resolveInstanceKey(string $componentName, array $props): string
    {
        foreach (['instanceId', 'id'] as $key) {
            if (! empty($props[$key]) && is_scalar($props[$key])) {
                return (string) $props[$key];
            }
        }

        $scalars = [];

        foreach ($props as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $scalars[$key] = $value;
            }
        }

        ksort($scalars);

        return self::fingerprint($componentName.'|'.json_encode($scalars, JSON_THROW_ON_ERROR));
    }

    public function declare(string $name): string
    {
        $this->declared[$name] = true;

        return $this->get($name);
    }

    public function has(string $name): bool
    {
        return isset($this->declared[$name]) || isset($this->ids[$name]);
    }

    public function get(string $name): string
    {
        if (! isset($this->ids[$name])) {
            $this->ids[$name] = 'sprout-'.$this->instanceKey.'-'.$name;
        }

        return $this->ids[$name];
    }

    public function instanceKey(): string
    {
        return $this->instanceKey;
    }

    public static function fingerprint(string $input): string
    {
        $hash = 5381;
        $length = strlen($input);

        for ($i = 0; $i < $length; $i++) {
            $hash = ((($hash << 5) + $hash) + ord($input[$i])) & 0xFFFFFFFF;
        }

        return str_pad(dechex($hash), 8, '0', STR_PAD_LEFT);
    }
}
