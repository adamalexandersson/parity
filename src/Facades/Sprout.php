<?php

namespace Sprout\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Sprout\Config\ConfigCollector collector()
 * @method static \Sprout\Registries\TransformRegistry transforms()
 * @method static \Sprout\Registries\ComponentRegistry components()
 * @method static \Sprout\Render\SchemaRenderer renderer()
 * @method static void discoverComponents()
 *
 * @see \Sprout\Sprout
 */
class Sprout extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'sprout';
    }
}
