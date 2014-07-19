<?php

namespace Gasp\Task;

use Gasp\Exception;
use Gasp\Run;

abstract class TaskAbstract implements TaskInterface
{
    /**
     * @var Run
     */
    protected $gasp;

    public function __construct(array $options)
    {
        $this->setOptions($options);
    }

    public function setGasp(Run $gasp)
    {
        $this->gasp = $gasp;

        return $this;
    }

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
}
