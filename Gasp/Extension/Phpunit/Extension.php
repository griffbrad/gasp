<?php

namespace Gasp\Extension\Phpunit;

use Gasp\ExtensionInterface;
use Gasp\Run;

class Extension implements ExtensionInterface
{
    public function extend(Run $gasp)
    {
        $gasp->classMap('phpunit', new ClassMap());

        /**
         * Add an alias to the runTests tasks, because it's by far the most
         * common task people will run from this extension.
         */
        $gasp->task('phpunit', 'phpunit.runTests');
    }
}
