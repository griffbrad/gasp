<?php

namespace Gasp\Extension\Vagrant;

use Gasp\ClassMap as CoreClassMap;

class ClassMap extends CoreClassMap
{
    protected $classes = array(
        'sync' => '\Gasp\Extension\Vagrant\Task\Sync'
    );
}
