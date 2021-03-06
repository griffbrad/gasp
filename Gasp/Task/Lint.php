<?php

/**
 * Gasp
 *
 * @link https://github.com/griffbrad/gasp
 */

namespace Gasp\Task;

use Gasp\Exception;
use Gasp\Result;
use Gasp\Validate;
use RecursiveDirectoryIterator;

/**
 * Use PHP's built-in linter to check the syntax of your PHP code.  To use this command,
 * you'll have to supply two pieces of information:
 *
 * 1) setPhp(): The location of the php command.
 *
 * 2) addPath()/setPaths(): At least one folder to look in for files.
 *
 * By default this command will only look at files ending in ".php".  You can
 * add additional file extensions (e.g. phtml) by calling addExtension().
 *
 * Example gaspfile definition:
 *
 * <code>
 * $gasp->lint()
 *     ->setPhp('/usr/bin/php')
 *     ->addPath('Gasp');
 * </code>
 *
 * Example usage:
 *
 * <code>
 * ./vendor/bin/gasp lint
 * </code>
 */
class Lint extends TaskAbstract implements PathsInterface
{
    /**
     * The location of the PHP command.
     *
     * @var string
     */
    private $php;

    /**
     * The paths where the task should look for PHP files.
     *
     * @var array
     */
    private $paths = array();

    /**
     * The file extensions that should be linted.
     *
     * @var array
     */
    private $extensions = array('php');

    /**
     * Set the location of the PHP command.
     *
     * @param $php
     * @return $this
     */
    public function setPhp($php)
    {
        $this->php = $php;

        return $this;
    }

    /**
     * Add another path to look for files in.
     *
     * @param $path
     * @return $this
     */
    public function addPath($path)
    {
        $this->paths[] = $path;

        return $this;
    }

    /**
     * Override the current paths.
     *
     * @param array $paths
     * @return $this
     */
    public function setPaths(array $paths)
    {
        $this->paths = $paths;

        return $this;
    }

    /**
     * Get all the paths assigned to this task.
     *
     * @return array
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * Add a file extension to look for files in.
     *
     * @param $extension
     * @return $this
     */
    public function addExtension($extension)
    {
        $this->extensions[] = ltrim($extension, '.');

        return $this;
    }

    /**
     * Override the current file extensions.
     *
     * @param array $extensions
     * @return $this
     */
    public function setExtensions($extensions)
    {
        $this->extensions = array_map(
            function ($extension) {
                return ltrim($extension, '.');
            },
            $extensions
        );

        return $this;
    }

    /**
     * Get all the file extensions assigned to this task.
     *
     * @return array
     */
    public function getExtensions()
    {
        return $this->extensions;
    }

    /**
     * Run the task, checking the syntax of all PHP files.
     *
     * @return Result
     */
    public function run()
    {
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
                    $execResult = $this->gasp->exec($cmd);

                    if ($execResult->isFailure()) {
                        $failures += 1;

                        $output[] = (string) $file;
                        $output[] = str_repeat('-', strlen($file));
                        $output[] = '';
                        $output[] = $execResult->getOutput();
                        $output[] = '';
                    }
                }
            }
        }

        $taskResult = $this->gasp->result();

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

    /**
     * Ensure the PHP command is available and that at least one path is defined.
     *
     * @throws Exception
     */
    public function validate()
    {
        Validate::checkExecutable($this->php, '\Gasp\Exception');

        if (!count($this->paths)) {
            throw new Exception('Cannot run lint task without any paths defined.');
        }
    }

    /**
     * Check to see if the supplied file has a valid file extension.
     *
     * @param string $file
     * @return bool
     */
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
