<?php

/**
 * Gasp
 *
 * @link https://github.com/griffbrad/gasp
 */

namespace Gasp;

use Closure;
use Gasp\Result\Aggregate as AggregateResult;
use Gasp\Result\ResultInterface;
use Gasp\Task\TaskInterface;

/**
 * This class is responsible for executing gasp and handling the customizations
 * devs add in their gaspfiles.
 */
class Run
{
    /**
     * The class maps used to locate and instantiate task objects.
     *
     * @var ClassMap
     */
    private $classMaps = array();

    /**
     * The directory we're executing in.
     *
     * @var string
     */
    private $workingDirectory;

    /**
     * $_SERVER vars.  Typically, you'll be using $_SERVER itself, but  during
     * tests, we may override with setServerVars().
     *
     * @var array
     */
    private $serverVars;

    /**
     * Any tasks that have been setup.  Could be custom tasks, in which case the
     * values in this array could be closures or arrays.  Or, they could be built-in
     * tasks, in which case they'd be TaskInterface implementers.
     *
     * @var array
     */
    private $tasks = array();

    /**
     * Optionally supply a custom class map and working directory for this runner.
     *
     * @param ClassMap $classMap
     * @param string $workingDirectory
     */
    public function __construct(ClassMap $classMap = null, $workingDirectory = null)
    {
        $this->classMap('default', ($classMap ?: new ClassMap()));

        $this->workingDirectory = rtrim($workingDirectory ?: getcwd(), '/');

        if (!file_exists($this->workingDirectory)) {
            throw new Exception("Working directory could not be found: {$this->workingDirectory}.");
        }

        if (!is_dir($this->workingDirectory)) {
            throw new Exception("Working directory is not in fact a directory: {$this->workingDirectory}.");
        }

        chdir($this->workingDirectory);
    }

    /**
     * Get the working directory (the directory from which gasp is being run).
     *
     * @return string
     */
    public function getWorkingDirectory()
    {
        return $this->workingDirectory;
    }

    /**
     * Extend Gasp with the supplied extension.
     *
     * @param ExtensionInterface $extension
     * @return $this
     */
    public function extend(ExtensionInterface $extension)
    {
        $extension->extend($this);

        return $this;
    }

    /**
     * Register a new class map with this gasp instance.
     *
     * @param $name
     * @param ClassMap $classMap
     * @return $this
     */
    public function classMap($name, ClassMap $classMap)
    {
        $classMap
            ->setGasp($this)
            ->setName($name);

        $this->classMaps[$name] = $classMap;

        return $this;
    }

    /**
     * Define a custom task with the supplied name and definition.  The
     * definition can be an array, string (matching an existing task name;
     * basically an alias) or closure.
     *
     * @param string $name
     * @param mixed $definition
     * @return $this
     * @throws Exception
     */
    public function task($name, $definition)
    {
        if (!$this->isValidTaskType($definition)) {
            throw new Exception('Custom tasks must be callbacks or arrays.');
        }

        $this->tasks[$name] = $definition;

        return $this;
    }

    /**
     * Kick off execution.
     *
     * @throws Exception
     */
    public function run()
    {
        $gaspfile = $this->findGaspfile();

        /** @noinspection PhpUnusedLocalVariableInspection */
        $gasp = $this;

        chdir(dirname($gaspfile));

        require $gaspfile;

        $name   = $this->getSelectedTaskName();
        $result = $this->runTaskByName($name);

        if (!$result instanceof ResultInterface) {
            throw new Exception('Tasks must return a result object.');
        }

        $this->displayOutput($result);
    }

    /**
     * Look up the task associated with the supplied name and run it.
     *
     * @param string $name
     * @return ResultInterface
     */
    public function runTaskByName($name)
    {
        return $this->runTask($this->getTaskByName($name));
    }

    /**
     * Get the task matching the supplied name.  If no task has already been added
     * for the name, we will fall back to looking in the class map.
     *
     * @param $name
     * @return mixed
     */
    public function getTaskByName($name)
    {
        $name = strtolower($name);

        if (isset($this->tasks[$name])) {
            return $this->tasks[$name];
        }

        if (false === strpos($name, '.')) {
            return $this->$name();
        } else {
            list($classMap, $name) = explode('.', $name);

            return $this->$classMap()->$name();
        }
    }

    /**
     * Run the supplied task, which can be one of several types: TaskInterface,
     * closure/callback, string, or array.  If an array is supplied, it will be
     * iterated over and recursively call runTask().  The results of that recursive
     * execution will be bundled into an AggregateResult object and returned.
     *
     * @param mixed $task
     * @return ResultInterface
     * @throws Exception
     */
    public function runTask($task)
    {
        if ($task instanceof TaskInterface) {
            $task->validate();
            return $task->run();
        } elseif ($task instanceof Closure) {
            return call_user_func($task);
        } elseif (is_string($task)) {
            return $this->runTaskByName($task);
        } elseif (is_array($task)) {
            $aggregate = $this->aggregate();

            foreach ($task as $subTask) {
                $aggregate->addResult($this->runTask($subTask));
            }

            return $aggregate;
        }

        throw new Exception('Invalid task type.  Must be TaskInterface, function, array, or string.');
    }

    /**
     * Display the result of running the selected tasks.
     *
     * @param ResultInterface $result
     * @return void
     */
    public function displayOutput(ResultInterface $result)
    {
        echo $result->display();
    }

    /**
     * Create a new Result object that can be used when writing tasks.
     *
     * @param array $options
     * @return Result
     */
    public function result(array $options = array())
    {
        return new Result($options);
    }

    /**
     * Return an aggregate result object, which can be useful if you have
     * custom task that bundles up several others.
     *
     * @return AggregateResult
     */
    public function aggregate()
    {
        return new AggregateResult();
    }

    /**
     * When calling an unknown method, we attempt to grab a task or class map
     * matching the method name.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, array $args)
    {
        $method = strtolower($method);

        if (!isset($this->classMaps[$method])) {
            return $this->classMaps['default']->$method($args);
        } else {
            $options = (isset($args[0]) && is_array($args[0]) ? $args[0] : array());

            /* @var $map ClassMap */
            $map = $this->classMaps[$method];

            if (count($options)) {
                $map->setOptions($options);
            }

            return $map;
        }
    }

    /**
     * Find the gaspfile that will be used to define the available tasks.
     *
     * @return string
     * @throws Exception
     */
    public function findGaspfile()
    {
        $validNames = array('gaspfile', 'Gaspfile', 'gaspfile.php', 'Gaspfile.php');
        $gaspfile   = null;

        foreach ($validNames as $name) {
            $fullPath = $this->workingDirectory . '/' . $name;

            if (file_exists($fullPath) && is_readable($fullPath)) {
                $gaspfile = $fullPath;
                break;
            }
        }

        if (null === $gaspfile) {
            throw new Exception('Could not find or read gaspfile.');
        }

        return $gaspfile;
    }

    public function registerTaskInstance($name, TaskInterface $task)
    {
        $this->tasks[$name] = $task;

        return $this;
    }

    /**
     * Override the stock $_SERVER superglobal.  Typically only used in testing.
     *
     * @param array $serverVars
     * @return $this
     */
    public function setServerVars(array $serverVars)
    {
        $this->serverVars = $serverVars;

        return $this;
    }

    /**
     * Get the $_SERVER vars.  Call this instead of accessing $_SERVER directly so
     * that the vars can be replaced during testing.
     *
     * @return mixed
     */
    public function getServerVars()
    {
        if (null === $this->serverVars) {
            return $_SERVER;
        } else {
            return $this->serverVars;
        }
    }

    /**
     * Get the selected taskname, either from the first argument supplied to the
     * command-line or "default", if no arguments were specified.
     *
     * @return string
     */
    private function getSelectedTaskName()
    {
        $serverVars = $this->getServerVars();

        if (isset($serverVars['argv'][1])) {
            $name = $serverVars['argv'][1];
        } else {
            $name = 'default';
        }

        return $name;
    }

    /**
     * Ensure the supplied task is either a Closure, implements TaskInterface,
     * or is an array or string.
     *
     * @param mixed $task
     * @return bool
     */
    private function isValidTaskType($task)
    {
        return $task instanceof Closure ||
            $task instanceof TaskInterface ||
            is_string($task) ||
            is_array($task);
    }
}
