<?php

/**
 * Gasp
 *
 * @link https://github.com/griffbrad/gasp
 */

namespace Gasp\Task;

use Gasp\ClassMap;
use Gasp\Run;
use Gasp\SetOptions;

/**
 * A simple abstract base class that can be used to implement some of the
 * common task utility methods.
 */
abstract class TaskAbstract implements TaskInterface
{
    use SetOptions;

    /**
     * The gasp Run object.
     *
     * @var Run
     */
    protected $gasp;

    /**
     * The class map this task was instantiated by.
     *
     * @var ClassMap
     */
    protected $classMap;

    /**
     * Allow users to set multiple options on a task at once by passing
     * in an array of key-value pairs.
     *
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        $this->setOptions($options);
    }

    /**
     * Pass the gasp Run object to the task so that it can be used to run
     * other tasks, etc.
     *
     * @param Run $gasp
     * @return $this|TaskInterface
     */
    public function setGasp(Run $gasp)
    {
        $this->gasp = $gasp;

        return $this;
    }

    /**
     * Provide a reference to the class map that instantiated this task.  Useful
     * when the class map has its own setters/getters for configuration of multiple
     * nested tasks.
     *
     * @param ClassMap $classMap
     * @return $this
     */
    public function setClassMap(ClassMap $classMap)
    {
        $this->classMap = $classMap;

        return $this;
    }
}
