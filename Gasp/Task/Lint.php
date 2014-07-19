<?php

namespace Gasp\Task;

use Gasp\Exception;
use Gasp\Result;
use RecursiveDirectoryIterator;

class Lint extends TaskAbstract
{
    private $php;

    private $paths = array();

    private $extensions = array('php');

    public function setPhp($php)
    {
        $this->php = $php;

        return $this;
    }

    public function addPath($path)
    {
        $this->paths[] = $path;

        return $this;
    }

    public function setPaths(array $paths)
    {
        $this->paths = $paths;

        return $this;
    }

    public function addExtension($extension)
    {
        $this->extensions[] = $extension;

        return $this;
    }

    public function setExtensions($extensions)
    {
        $this->extensions = $extensions;

        return $this;
    }

    public function run()
    {
        $this->validate();

        $result   = $this->gasp->result();
        $output   = array();
        $failures = 0;

        foreach ($this->paths as $path) {
            $files = new RecursiveDirectoryIterator($path);

            foreach ($files as $file) {
                if ($this->fileMatchesExtension($file)) {
                    $cmd = sprintf(
                        '%s -l %s',
                        $this->php,
                        escapeshellarg($file)
                    );

                    /* @var $execResult Result */
                    $execResult = $this->gasp->exec()
                        ->setCmd($cmd)
                        ->run();

                    if ($execResult->isFailure()) {
                        $failures += 1;

                        $output[] = $file;
                        $output[] = str_repeat('-', strlen($file));
                        $output[] = '';

                        foreach ($execResult->getOutput() as $line) {
                            $output[] = $line;
                        }

                        $output[] = '';
                    }
                }
            }
        }

        $taskResult = new Result();

        if (!$failures) {
            $taskResult
                ->setStatus(Result::SUCCESS)
                ->setMessage('No PHP errors found.');
        } else {
            $taskResult
                ->setStatus(Result::FAIL)
                ->setMessage("PHP errors found in {$failures} files.")
                ->setOutput($output);
        }


        return $taskResult;
    }

    public function validate()
    {
        if (!$this->php) {
            throw new Exception('No php command defined.');
        }

        if (!file_exists($this->php)) {
            throw new Exception("php command not found at: {$this->php}");
        }

        if (!is_executable($this->php)) {
            throw new Exception('php command is not executable.');
        }

        if (!count($this->paths)) {
            throw new Exception('Cannot run lint task without any paths defined.');
        }
    }

    private function fileMatchesExtension($file)
    {
        foreach ($this->extensions as $extension) {
            if (preg_match("/\.{$extension}$/", $file)) {
                return true;
            }
        }

        return false;
    }
}
