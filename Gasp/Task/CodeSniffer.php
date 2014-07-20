<?php

namespace Gasp\Task;

use Gasp\Exception;
use Gasp\Result;

class CodeSniffer extends TaskAbstract
{
    private $phpcs;

    private $standard;

    private $paths = array();

    private $extensions = array('php');

    public function setPhpcs($phpcs)
    {
        $this->phpcs = $phpcs;

        return $this;
    }

    public function setStandard($standard)
    {
        $this->standard = $standard;

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

        $cmd = sprintf(
            '%s --report=json --standard=%s --extensions=%s %s',
            $this->phpcs,
            escapeshellarg($this->standard),
            escapeshellarg(implode(',', $this->extensions)),
            implode(' ', array_map('escapeshellarg', $this->paths))
        );

        $taskResult = $this->gasp->result();

        /* @var $execResult Result */
        $execResult = $this->gasp->exec()->setCmd($cmd)->run();

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

    public function validate()
    {
        if (!$this->phpcs) {
            throw new Exception('No phpcs command defined.');
        }

        if (!file_exists($this->phpcs)) {
            throw new Exception("phpcs command not found at: {$this->phpcs}");
        }

        if (!is_executable($this->phpcs)) {
            throw new Exception('phpcs command is not executable.');
        }

        if (!count($this->paths)) {
            throw new Exception('Cannot run sniff task without any paths defined.');
        }
    }

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
