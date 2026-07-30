<?php

use Parity\Component;

return Component::make('grid')
    ->category('object')
    ->modifier('cols', 'cols', breakpoint: 'md')
    ->toSchema();
