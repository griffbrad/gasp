<?php

namespace Gasp\Extension\Ssh;

use Gasp\ExtensionInterface;
use Gasp\Run;

class Extension implements ExtensionInterface
{
    public function extend(Run $gasp)
    {
        $gasp->classMap('ssh', new ClassMap());
    }
}
