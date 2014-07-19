<?php

/**
 * Gasp
 *
 * @link https://github.com/griffbrad/gasp
 */

namespace Gasp;

use Gasp\Task\TaskInterface;

class ClassMap
{
    protected $classes = array(
        'exec'  => '\Gasp\Task\Exec',
        'lint'  => '\Gasp\Task\Lint',
        'sniff' => '\Gasp\Task\CodeSniffer'
    );

    /**
     * @param string$name
     * @param array $args
     * @return TaskInterface
     * @throws Exception
     */
    public function factory($name, array $args)
    {
        $name = strtolower($name);

        if (!isset($this->classes[$name])) {
            throw new Exception("Could not find task with name: {$name}.");
        }

        $className = $this->classes[$name];

        return new $className($args);
    }

    public function register($taskName, $className)
    {
        $taskName = strtolower($taskName);

        $this->classes[$taskName] = $className;

        return $this;
    }
}
