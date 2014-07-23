<?php

namespace Gasp\Extension\Vagrant;

use Gasp\ExtensionInterface;
use Gasp\Run;

class Extension implements ExtensionInterface
{
    public function extend(Run $gasp)
    {
        $gasp->classMap('vagrant', new ClassMap());
    }
}
