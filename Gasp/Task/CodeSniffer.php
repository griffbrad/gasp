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

/**
 * Use PHP_CodeSniffer to check the style of your PHP code.  To use this command,
 * you'll have to supply two pieces of information:
 *
 * 1) setPhpcs(): The location of the phpcs command.
 *
 * 2) addPath()/setPaths(): At least one folder to look in for files.
 *
 * By default this command will only look at files ending in ".php".  You can
 * add additional file extensions (e.g. phtml) by calling addExtension().
 *
 * Example gaspfile definition:
 *
 * <code>
 * $gasp->sniff()
 *     ->setPhpcs('./vendor/bin/phpcs')
 *     ->setStandard('PSR2')
 *      ->addPath('Gasp');
 * </code>
 *
 * Example usage:
 *
 * <code>
 * ./vendor/bin/gasp sniff
 * </code>
 */
class CodeSniffer extends TaskAbstract implements PathsInterface
{
    /**
     * The location of the phpcs command.
     *
     * @var string
     */
    private $phpcs;

    /**
     * The coding standard that should be used to evaluate your code.  Can be
     * either the name of an installed standard or a path to an alternative.
     *
     * @var string
     */
    private $standard = 'PSR2';

    /**
     * The paths in which CodeSniffer can find files to scan.
     *
     * @var array
     */
    private $paths = array();

    /**
     * A list of file extensions that should ve evaluated.
     */
    private $extensions = array('php');

    /**
     * Set the location of the phpcs command.
     *
     * @param string $phpcs
     * @return $this
     */
    public function setPhpcs($phpcs)
    {
        $this->phpcs = $phpcs;

        return $this;
    }

    /**
     * Set the standard that should be used to evaluate the code.
     *
     * @param string $standard
     * @return $this
     */
    public function setStandard($standard)
    {
        $this->standard = $standard;

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
     * Run the task.
     *
     * @return Result
     */
    public function run()
    {
        $execResult = $this->execPhpcs();
        $taskResult = $this->gasp->result();

        if ($execResult->isFailure() && !$execResult->getOutput()) {
            $taskResult
                ->setStatus(Result::FAIL)
                ->setMessage('Could not run phpcs command.');
        } else {
            $results = @json_decode($execResult->getOutput(), true);

            if ($results) {
                $this->handlePhpcsResults($taskResult, $results);
            } else {
                $taskResult
                    ->setStatus(Result::FAIL)
                    ->setMessage('Could not parse phpcs output.');
            }
        }

        return $taskResult;
    }

    /**
     * Run the phpcs command with the exec() task and return the result.
     *
     * @return Result
     */
    public function execPhpcs()
    {
        $cmd = sprintf(
            '%s --report=json --standard=%s --extensions=%s %s',
            $this->phpcs,
            escapeshellarg($this->standard),
            escapeshellarg(implode(',', $this->extensions)),
            implode(' ', array_map('escapeshellarg', $this->paths))
        );

        return $this->gasp->exec()->setCmd($cmd)->run();
    }

    /**
     * Ensure that the necessary options have been set prior to executing
     * the task.
     *
     * @throws \Gasp\Exception
     */
    public function validate()
    {
        Validate::checkExecutable($this->phpcs, '\Gasp\Exception');

        if (!count($this->paths)) {
            throw new Exception('Cannot run sniff task without any paths defined.');
        }
    }

    /**
     * Render the output generated by phpcs and set the result status according
     * to whether any warnings or errors were generated.
     *
     * @param Result $result
     * @param array $phpcsOutput
     * @return Result
     */
    private function handlePhpcsResults(Result $result, array $phpcsOutput)
    {
        if (!$phpcsOutput['totals']['errors'] && !$phpcsOutput['totals']['warnings']) {
            $result
                ->setStatus(Result::SUCCESS)
                ->setMessage('PHP_CodeSniffer found no errors.');
        } else {
            $output = array();

            foreach ($phpcsOutput['files'] as $file => $results) {
                if (count($results['messages'])) {
                    $output[] = $file;
                    $output[] = str_repeat('-', strlen($file));
                    $output[] = '';

                    foreach ($results['messages'] as $message) {
                        $output[] = sprintf(
                            '%s: %s (Line %d, Column %d)',
                            $message['type'],
                            $message['message'],
                            $message['line'],
                            $message['column']
                        );
                    }

                    $output[] = '';
                }
            }

            $result->setOutput($output);

            if ($phpcsOutput['totals']['errors']) {
                $result
                    ->setStatus(Result::FAIL)
                    ->setMessage("PHP_CodeSniffer generated {$phpcsOutput['totals']['errors']} errors.");
            } elseif ($phpcsOutput['totals']['warnings']) {
                $result
                    ->setStatus(Result::WARN)
                    ->setMessage("PHP_CodeSniffer generated {$phpcsOutput['totals']['warnings']} warnings.");
            }
        }

        return $result;
    }
}
