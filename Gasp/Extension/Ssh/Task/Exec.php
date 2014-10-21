<?php

namespace Gasp\Extension\Ssh\Task;

use Gasp\Extension\Ssh\Exception;
use Gasp\Task\TaskAbstract;

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
        $cmd = sprintf(
            '%s -i %s -p %s %s@%s %s',
            $this->classMap->getSsh(),
            escapeshellarg($this->classMap->getPrivateKey()),
            escapeshellarg($this->classMap->getPort()),
            escapeshellarg($this->classMap->getUsername()),
            escapeshellarg($this->classMap->getHost()),
            escapeshellarg($this->cmd)
        );

        return $this->gasp->exec()
            ->setCmd($cmd)
            ->run();
    }

    public function validate()
    {
        if (!$this->cmd) {
            throw new Exception('You must define the command to execute.');
        }

        $this->classMap->validate();
    }
}
