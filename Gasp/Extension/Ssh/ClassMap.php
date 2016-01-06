<?php

namespace Gasp\Extension\Ssh;

use Gasp\ClassMap as CoreClassMap;
use Gasp\Extension\Ssh\Task\Exec as ExecTask;
use Gasp\Validate;

class ClassMap extends CoreClassMap
{
    protected $classes = array(
        'rsync' => '\Gasp\Extension\Ssh\Task\Rsync'
    );

    private $ssh = '/usr/bin/ssh';

    private $host;

    private $username;

    private $privateKey;

    private $port = 22;

    public function exec($cmd)
    {
        $task = new ExecTask();

        return $task
            ->setGasp($this->getGasp())
            ->setClassMap($this)
            ->setCmd($cmd)
            ->run();
    }

    public function setSsh($ssh)
    {
        $this->ssh = $ssh;

        return $this;
    }

    public function getSsh()
    {
        return $this->ssh;
    }

    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    public function setHostname($hostname)
    {
        return $this->setHost($hostname);
    }

    public function getHost()
    {
        return $this->host;
    }

    public function setPrivateKey($privateKey)
    {
        $this->privateKey = $this->expandHomePath($privateKey);

        return $this;
    }

    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function validate()
    {
        Validate::checkExecutable($this->getSsh(), '\Gasp\Exception\Ssh\Exception');

        if (!$this->getUsername()) {
            throw new Exception('You must define the SSH username.');
        }

        if (!$this->getHost()) {
            throw new Exception('You must define the SSH hostname.');
        }

        if (!$this->getPort()) {
            throw new Exception('You must define the SSH port.');
        }
    }

    private function expandHomePath($path)
    {
        return preg_replace('/^\~\//', $_SERVER['HOME'] . '/', $path);
    }
}
