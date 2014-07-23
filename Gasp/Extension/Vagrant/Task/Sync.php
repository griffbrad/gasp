<?php

namespace Gasp\Extension\Vagrant\Task;

use Gasp\Exception;
use Gasp\Result;
use Gasp\Task\TaskAbstract;

class Sync extends TaskAbstract
{
    private $rsync = '/usr/bin/rsync';

    public function run()
    {
        /* @var $result Result */
        $result = $this->gasp->exec()
            ->setCmd($this->buildRsyncCmd())
            ->run();

        if ($result->isFailure()) {
            return $result;
        } elseif (!$result->getOutput()) {
            $folder = $this->gasp->getWorkingDirectory();
            $result->setMessage("No files to sync in {$folder}.");
        } else {
            $result
                ->setStatus(Result::WARN)
                ->setMessage('Synced the following files:');
        }

        return $result;
    }

    public function validate()
    {
        if (!$this->rsync) {
            throw new Exception('No rsync command defined.');
        }

        if (!file_exists($this->rsync)) {
            throw new Exception("rsync command not found at: {$this->rsync}");
        }

        if (!is_executable($this->rsync)) {
            throw new Exception('rsync command is not executable.');
        }
    }

    private function buildRsyncCmd()
    {
        return sprintf(
            "%s --verbose --archive --delete -z " .
            "-e 'ssh -p 2222 -o StrictHostKeyChecking=no " .
            "-i \"/Users/bgriffith/.vagrant.d/insecure_private_key\"' " .
            "--exclude .vagrant/ --exclude .git/ --exclude .svn/ --exclude .idea/ " .
            "--exclude php_errors --exclude xdebug %s vagrant@127.0.0.1:%s",
            $this->rsync,
            escapeshellarg($this->gasp->getWorkingDirectory() . '/'),
            escapeshellarg($this->gasp->getWorkingDirectory() . '/')
        );
    }
}
