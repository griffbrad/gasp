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
     * The class map used to locate and instantiate task objects.
     *
     * @var ClassMap
     */
    private $classMap;

    /**
     * The directory we're executing in.
     *
     * @var string
     */
    private $workingDirectory;

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
        $this->classMap         = ($classMap ?: new ClassMap());
        $this->workingDirectory = ($workingDirectory ?: getcwd());
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
        if ($definition instanceof Closure && !is_array($definition)) {
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

        $name   = $this->getTaskName();
        $task   = $this->getTask($name);
        $result = $this->runTask($task);

        if (!$result instanceof ResultInterface) {
            throw new Exception('Tasks must return a result object.');
        }

        $this->displayOutput($result);
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
            return $this->runTask($this->getTask($task));
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
     * When calling an unknown method, we attempt to grab a task from the
     * class map object.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, array $args)
    {
        $method = strtolower($method);

        if (!isset($this->tasks[$method])) {
            $task = $this->classMap->factory($method, $args);
            $task->setGasp($this);
            $this->tasks[$method] = $task;
        }

        return $this->tasks[$method];
    }

    /**
     * Find the gaspfile that will be used to define the available tasks.
     *
     * @return string
     * @throws Exception
     */
    public function findGaspfile()
    {
        $path = $this->workingDirectory . '/gaspfile';

        if (!file_exists($path)) {
            throw new Exception('Could not find gaspfile.');
        }

        if (!is_readable($path)) {
            throw new Exception('Could not read gaspfile.');
        }

        return $path;
    }

    /**
     * Get the selected taskname, either from the first argument supplied to the
     * command-line or "default", if no arguments were specified.
     *
     * @return string
     */
    private function getTaskName()
    {
        if (isset($_SERVER['argv'][1])) {
            $name = $_SERVER['argv'][1];
        } else {
            $name = 'default';
        }

        return $name;
    }

    /**
     * Get the task matching the supplied name.  If no task has already been added
     * for the name, we will fall back to looking in the class map.
     *
     * @param $name
     * @return mixed
     * @throws Exception
     */
    private function getTask($name)
    {
        $name = strtolower($name);

        if (!isset($this->tasks[$name]) && !$this->$name()) {
            throw new Exception("No task with name '{$name}' defined.");
        }

        return $this->tasks[$name];
    }
}
