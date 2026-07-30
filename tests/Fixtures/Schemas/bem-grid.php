<?php

use Parity\Component;

return Component::make('grid')
    ->category('object')
    ->modifier('colsMd', 'cols', breakpoint: 'md')
    ->toSchema();
