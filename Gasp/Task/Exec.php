<?php

namespace Gasp\Task;

use Gasp\Exception;
use Gasp\Result;

class Exec extends TaskAbstract
{
    private $cmd;

    public function setCmd($cmd)
    {
        $this->cmd = $cmd;

        return $this;
    }

    public function run()
    {
        $this->validate();

        exec($this->cmd, $output, $exitStatus);

        $result = $this->gasp->result();

        if (0 !== $exitStatus) {
            $result
                ->setStatus(Result::FAIL)
                ->setMessage("Command failed to execute: {$this->cmd}");
        } else {
            $result
                ->setStatus(Result::SUCCESS)
                ->setMessage("Command completed successfully: {$this->cmd}");
        }

        $result->setOutput($output);

        return $result;
    }

    public function validate()
    {
        if (!$this->cmd) {
            throw new Exception('cmd required to run exec task.');
        }
    }
}
