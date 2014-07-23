<?php

namespace Gasp;

class ClassMapProxy
{
    private $gasp;

    private $name;

    private $classMap;

    public function __construct(Run $gasp, $name, ClassMap $classMap, array $options = array())
    {
        $this->gasp     = $gasp;
        $this->name     = $name;
        $this->classMap = $classMap;

        $this->classMap->setOptions($options);
    }

    public function __call($method, array $args)
    {
        $task = $this->classMap->factory($method, $args);

        $task->setGasp($this->gasp);

        if ('default' === $this->name) {
            $this->gasp->registerTaskInstance($method, $task);
        } else {
            $this->gasp->registerTaskInstance($this->name . '.' . $method, $task);
        }

        return $task;
    }
}
