<?php

use Parity\Component;

return Component::make('card')
    ->category('component')
    ->modifier('featured')
    ->modifier('size')
    ->is('active')
    ->has('icon')
    ->modifier('hiddenFlag')
    ->when('showHidden')
    ->toSchema();
