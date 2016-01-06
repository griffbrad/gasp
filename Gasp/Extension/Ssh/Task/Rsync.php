<?php

namespace Gasp\Extension\Ssh\Task;

use Gasp\Task\TaskAbstract;
use Gasp\Validate;

class Rsync extends TaskAbstract
{
    private $rsync;

    private $excludeVcs = true;

    private $excludeOsCruft = true;

    private $localPath;

    private $remotePath;

    private $excludes = array();

    public function setRsync($rsync)
    {
        $this->rsync = $rsync;

        return $this;
    }

    public function addExclude($exclude)
    {
        $this->excludes[] = $exclude;

        return $this;
    }

    public function exclude($exclude)
    {
        return $this->addExclude($exclude);
    }

    public function setExcludeOsCruft($excludeOsCruft)
    {
        $this->excludeOsCruft = $excludeOsCruft;

        return $this;
    }

    public function setExcludeVcs($excludeVcs)
    {
        $this->excludeVcs = $excludeVcs;

        return $this;
    }

    public function setLocalPath($localPath)
    {
        $this->localPath = $this->normalizePath(realpath($localPath));

        return $this;
    }

    public function getLocalPath()
    {
        return $this->localPath;
    }

    public function setRemotePath($remotePath)
    {
        $this->remotePath = $this->normalizePath($remotePath);

        return $this;
    }

    public function getRemotePath()
    {
        return $this->remotePath;
    }

    public function run()
    {
        $ssh = sprintf(
            '%s -p %s -i %s',
            $this->classMap->getSsh(),
            escapeshellarg($this->classMap->getPort()),
            escapeshellcmd($this->classMap->getPrivateKey())
        );

        $cmd = sprintf(
            "%s -az -e %s --out-format='%%n' %s %s %s",
            $this->rsync,
            escapeshellarg($ssh),
            $this->assembleAdditionalCmdArgs(),
            escapeshellarg($this->localPath),
            escapeshellarg($this->remotePath)
        );

        return $this->gasp->exec($cmd);
    }

    private function assembleAdditionalCmdArgs()
    {
        $args = [];

        if ($this->excludeVcs) {
            $args[] = '--cvs-exclude';
            $args[] = '--exclude=.git';
        }

        return implode(' ', $args);
    }

    public function validate()
    {
        $this->classMap->validate();

        Validate::checkExecutable($this->rsync, '\Gasp\Extension\Ssh\Exception');
    }

    private function normalizePath($path)
    {
        return rtrim($path, '/') . '/';
    }
}
