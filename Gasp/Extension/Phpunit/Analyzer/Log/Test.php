<?php

namespace Gasp\Extension\Phpunit\Analyzer\Log;

use Gasp\SetOptions;

class Test
{
    use SetOptions;

    private $name;

    private $trace = array();

    private $message;

    public function __construct(array $options = array())
    {
        $this->setOptions($options);
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setTrace(array $trace)
    {
        $this->trace = $trace;

        return $this;
    }

    public function getTrace()
    {
        return $this->trace;
    }

    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    public function getMessage()
    {
        return $this->message;
    }
}
