<?php

namespace Sprout\Support;

use Sprout\Exceptions\SchemaException;

final class IdInterpolator
{
    /**
     * Replace `{name}` placeholders with generated instance IDs.
     */
    public static function interpolate(
        string $value,
        InstanceIds $ids,
        bool $debug = false,
        ?string $component = null,
    ): string {
        return (string) preg_replace_callback(
            '/\{([a-zA-Z_][\w-]*)\}/',
            function (array $matches) use ($ids, $debug, $component, $value) {
                $name = $matches[1];

                if (! $ids->has($name)) {
                    if ($debug) {
                        throw new SchemaException(
                            "Unknown id placeholder \"{$name}\" in \"{$value}\". Declare it with uniqueId()/idRef() first.",
                            $component,
                        );
                    }

                    return $matches[0];
                }

                return $ids->get($name);
            },
            $value
        );
    }

    public static function shouldInterpolate(mixed $value, ?bool $flag = null): bool
    {
        if ($flag === false) {
            return false;
        }

        if ($flag === true) {
            return is_string($value);
        }

        return is_string($value) && preg_match('/\{[a-zA-Z_][\w-]*\}/', $value) === 1;
    }
}
