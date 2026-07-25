<?php

namespace Sprout\Support;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

final class ComponentReflector
{
    /**
     * @param  class-string  $class
     * @return list<array{name: string, type: string, default: mixed, required: bool}>
     */
    public static function constructorProps(string $class): array
    {
        if (! class_exists($class)) {
            return [];
        }

        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return [];
        }

        $props = [];

        foreach ($constructor->getParameters() as $parameter) {
            if ($parameter->getName() === 'attributes') {
                continue;
            }

            $props[] = [
                'name' => $parameter->getName(),
                'type' => self::typeName($parameter),
                'default' => $parameter->isDefaultValueAvailable()
                    ? self::exportDefault($parameter->getDefaultValue())
                    : null,
                'required' => ! $parameter->isDefaultValueAvailable() && ! $parameter->allowsNull(),
            ];
        }

        return $props;
    }

    protected static function typeName(ReflectionParameter $parameter): string
    {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType) {
            return $type->getName();
        }

        if ($type instanceof ReflectionUnionType) {
            return implode('|', array_map(
                fn (ReflectionNamedType $named) => $named->getName(),
                array_filter($type->getTypes(), fn ($t) => $t instanceof ReflectionNamedType)
            ));
        }

        return 'mixed';
    }

    protected static function exportDefault(mixed $value): mixed
    {
        if (is_scalar($value) || $value === null) {
            return $value;
        }

        if (is_array($value)) {
            return $value;
        }

        return null;
    }
}
