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
        'sniff' => '\Gasp\Task\CodeSniffer',
        'watch' => '\Gasp\Task\Watch'
    );

    /**
     * Aliases that can be used to run the commands defined in the classes
     * array.  The keys in $aliases should be the canonical command name,
     * and the values should be an array of available aliases.
     *
     * @var array
     */
    protected $aliases = array(
        'sniff' => array('phpcs', 'codeSniffer')
    );

    /**
     * Allow users to set multiple options on a class map at once by passing
     * in an array of key-value pairs.
     *
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        $this->setOptions($options);
    }

    /**
     * Allow users to set multiple options on a class map at once by passing
     * in an array of key-value pairs.
     *
     * @param array $options
     */
    public function setOptions(array $options)
    {
        foreach ($options as $name => $value) {
            $setter = 'set' . ucfirst($name);

            if (!method_exists($this, $setter)) {
                throw new Exception("Option '{$name}' does not exist.");
            } else {
                $this->$setter($value);
            }
        }

        return $this;
    }

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
        $name    = strtolower($name);
        $options = (isset($args[0]) && is_array($args[0]) ? $args[0] : array());

        foreach ($this->aliases as $command => $aliases) {
            if (array_key_exists($name, $aliases)) {
                $name = $command;
                break;
            }
        }

        if (!isset($this->classes[$name])) {
            throw new Exception("Could not find task with name: {$name}.");
        }

        $className = $this->classes[$name];

        return new $className($options);
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
