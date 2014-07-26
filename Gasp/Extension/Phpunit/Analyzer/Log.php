<?php

namespace Gasp\Extension\Phpunit\Analyzer;

use Gasp\Exception;
use Gasp\Extension\Phpunit\Analyzer\Log\Test;
use Gasp\Render\Header;
use Gasp\Render\Table;
use Gasp\Terminal;

class Log implements AnalyzerInterface
{
    private $file;

    private $testCount = 0;

    private $failedTests = array();

    private $skippedTests = array();

    public function getTestCount()
    {
        return $this->testCount;
    }

    public function getFailureCount()
    {
        return count($this->failedTests);
    }

    public function getSkippedCount()
    {
        return count($this->skippedTests);
    }

    public function setFile($file)
    {
        $this->file = $file;

        return $this;
    }

    public function deleteFile()
    {
        if (file_exists($this->file)) {
            unlink($this->file);
        }
    }

    public function getCmdArg()
    {
        return sprintf('--log-json %s', escapeshellarg($this->file));
    }

    public function analyze()
    {
        // Reset counters in case we're running multiple times
        $this->skippedCount = 0;
        $this->testCount    = 0;
        $this->failureCount = 0;

        $results = $this->parseJsonData();

        foreach ($results as $event) {
            if ('test' !== $event['event']) {
                continue;
            }

            $this->testCount += 1;

            if ('pass' !== $event['status']) {
                $test = new Test(
                    array(
                        'name'    => $event['test'],
                        'trace'   => $event['trace'],
                        'message' => $event['message']
                    )
                );

                if ($this->testWasSkipped($event)) {
                    $this->skippedTests[] = $test;
                } else {
                    $this->failedTests[] = $test;
                }
            }
        }
    }

    public function isSuccess()
    {
        return 0 === count($this->skippedTests) && 0 === count($this->failedTests);
    }

    public function isWarning()
    {
        return 0 < count($this->skippedTests) && 0 === count($this->failedTests);
    }

    public function isFailure()
    {
        return 0 < count($this->failedTests);
    }

    public function getMessage()
    {
        if ($this->isSuccess()) {
            return sprintf('Successfully ran %d tests.', $this->testCount);
        } elseif ($this->isWarning()) {
            return sprintf('No failures, but skipped %d of %d tests.', count($this->skippedTests), $this->testCount);
        } else {
            return sprintf('Failed %d of %d tests.', count($this->failedTests), $this->testCount);
        }
    }

    public function getOutput(Terminal $terminal)
    {
        $output = [];
        $header = new Header($terminal);

        if (count($this->failedTests)) {
            $failedOutput = [];

            /* @var $test Test */
            foreach ($this->failedTests as $test) {
                $testOutput = '';

                $testOutput .= $header->render($test->getName(), $test->getMessage());
                $testOutput .= PHP_EOL;
                $testOutput .= $this->renderTrace($test->getTrace());

                $failedOutput[] = $testOutput;
            }

            $output[] = implode(PHP_EOL, $failedOutput);
        }

        if (count($this->skippedTests)) {
            $table = new Table();
            $table->setHeaders(['Skipped tests', 'Message']);

            /* @var $test Test */
            foreach ($this->skippedTests as $test) {
                $table->addRow([$test->getName(), preg_replace('/^Skipped Test: /i', '', $test->getMessage())]);
            }

            $output[] = $table->render();
        }

        return implode(PHP_EOL, $output);
    }

    private function renderTrace(array $trace)
    {
        $out = '';

        foreach ($trace as $index => $step) {
            if ($step['class']) {
                $out .= sprintf('%d. %s%s%s', $index + 1, $step['class'], $step['type'], $step['function']);
            } else {
                $out .= $step['function'];
            }

            $out .= sprintf(
                '(%s)',
                implode(
                    ', ',
                    array_map(
                        function ($arg) {
                            if (is_string($arg)) {
                                if (16 < strlen($arg)) {
                                    return '"' . substr($arg, 0, 16) . '..."';
                                } else {
                                    return '"' . $arg . '"';
                                }
                            } elseif (is_bool($arg)) {
                                return ($arg ? 'true' : 'false');
                            } else {
                                return $arg;
                            }
                        },
                        $step['args']
                    )
                )
            );

            $out .= PHP_EOL;

            $out .= sprintf('    - %s (Line %d)', $step['file'], $step['line']) . PHP_EOL;
        }

        return $out;
    }

    private function parseJsonData()
    {
        $data = @json_decode(
            '[' . str_replace('}{', '},{', file_get_contents($this->file)) . ']',
            true
        );

        if (!$data) {
            throw new Exception('Could not parse phpunit JSON log.');
        }

        return $data;
    }

    private function testWasSkipped(array $logEvent)
    {
        return isset($logEvent['trace'][0]) &&
            isset($logEvent['trace'][0]['function']) &&
            'markTestSkipped' === $logEvent['trace'][0]['function'];
    }
}
