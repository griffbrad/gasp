<?php

/**
 * Gasp
 *
 * @link https://github.com/griffbrad/gasp
 */

namespace Gasp;

use Gasp\Task\TaskInterface;

/**
 * This map ties all the built-in tasks to their API names.  You could register
 * custom tasks on an instance of this class and then inject that instance into
 * Run to modify default gasp behavior.
 */
class ClassMap
{
    /**
     * The task classes currently registered in this class map.
     *
     * @var array
     */
    protected $classes = array(
        'exec'  => '\Gasp\Task\Exec',
        'lint'  => '\Gasp\Task\Lint',
        'sniff' => '\Gasp\Task\CodeSniffer'
    );

    /**
     * Get a new instance of the task matching the supplied name.
     *
     * @param string $name
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

    /**
     * Register a new task name and class with this class map.
     *
     * @param string $taskName
     * @param string $className
     * @return $this
     */
    public function register($taskName, $className)
    {
        $taskName = strtolower($taskName);

        $this->classes[$taskName] = $className;

        return $this;
    }
}
