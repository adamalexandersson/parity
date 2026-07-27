<?php

namespace Parity\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Parity\Config\ConfigCollector collector()
 * @method static \Parity\Registries\TransformRegistry transforms()
 * @method static \Parity\Render\SchemaRenderer renderer()
 * @method static void discoverComponents()
 * @method static void rediscoverComponents()
 *
 * @see \Parity\Parity
 */
class Parity extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'parity';
    }
}
