<?php

namespace Sprout\Support;

use Sprout\Exceptions\SchemaException;

final class IdInterpolator
{
    /**
     * Replace `{name}` placeholders with generated instance IDs.
     * Escape `{{name}}` to a literal `{name}`.
     */
    public static function interpolate(
        string $value,
        InstanceIds $ids,
        bool $debug = false,
        ?string $component = null,
    ): string {
        return (string) preg_replace_callback(
            '/\{\{([a-zA-Z_][\w-]*)\}\}|\{([a-zA-Z_][\w-]*)\}/',
            function (array $matches) use ($ids, $debug, $component, $value) {
                if (($matches[1] ?? '') !== '') {
                    return '{'.$matches[1].'}';
                }

                $name = $matches[2] ?? '';

                if ($name === '') {
                    return $matches[0];
                }

                if (! $ids->has($name)) {
                    if ($debug) {
                        throw new SchemaException(
                            "Unknown id placeholder \"{$name}\" in \"{$value}\". Declare it with uniqueId()/idRef() first.",
                            $component,
                        );
                    }

                    return '{'.$name.'}';
                }

                return $ids->get($name);
            },
            $value
        );
    }

    public static function shouldInterpolate(mixed $value): bool
    {
        return is_string($value) && preg_match('/\{\{?[a-zA-Z_][\w-]*\}/', $value) === 1;
    }
}
