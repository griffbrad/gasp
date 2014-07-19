<?php

namespace Gasp;

use Closure;
use Gasp\Result\Aggregate as AggregateResult;
use Gasp\Result\ResultInterface;
use Gasp\Task\TaskInterface;

class Run
{
    private $classMap;

    private $workingDirectory;

    private $tasks = array();

    public function __construct(ClassMap $classMap = null, $workingDirectory = null)
    {
        $this->classMap         = ($classMap ?: new ClassMap());
        $this->workingDirectory = ($workingDirectory ?: getcwd());
    }

    public function task($name, $definition)
    {
        if ($definition instanceof Closure && !is_array($definition)) {
            throw new Exception('Custom tasks must be callbacks or arrays.');
        }

        $this->tasks[$name] = $definition;

        return $this;
    }

    public function run()
    {
        $gaspfile = $this->findGaspfile();
        $gasp     = $this;

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

    public function runTask($task)
    {
        if ($task instanceof TaskInterface) {
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

    public function displayOutput(ResultInterface $result)
    {
        echo $result->display();
    }

    public function result(array $options = array())
    {
        return new Result($options);
    }

    public function aggregate()
    {
        return new AggregateResult();
    }

    public function __call($method, $args)
    {
        $method = strtolower($method);

        if (!isset($this->tasks[$method])) {
            $task = $this->classMap->factory($method, $args);
            $task->setGasp($this);
            $this->tasks[$method] = $task;
        }

        return $this->tasks[$method];
    }

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

    private function getTaskName()
    {
        if (isset($_SERVER['argv'][1])) {
            $name = $_SERVER['argv'][1];
        } else {
            $name = 'default';
        }

        return $name;
    }

    private function getTask($name)
    {
        $name = strtolower($name);

        if (!isset($this->tasks[$name]) && !$this->$name()) {
            throw new Exception("No task with name '{$name}' defined.");
        }

        return $this->tasks[$name];
    }
}
