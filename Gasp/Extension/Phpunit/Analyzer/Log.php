<?php

namespace Gasp\Extension\Phpunit\Analyzer;

class Log implements AnalyzerInterface
{
    private $file;

    private $testCount = 0;

    private $failureCount = 0;

    private $skippedCount = 0;

    private $log = array();

    public function getTestCount()
    {
        return $this->testCount;
    }

    public function getFailureCount()
    {
        return $this->failureCount;
    }

    public function getSkippedCount()
    {
        return $this->skippedCount;
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

        foreach ($results as $index => $event) {
            // Adding "skipped" key to all events so we can more easily track them
            $results[$index]['skipped'] = false;

            if ('test' !== $event['event']) {
                continue;
            }

            $this->testCount += 1;

            if ('error' === $event['status']) {
                if ($this->testWasSkipped($event)) {
                    $this->failureCount += 1;
                } else {
                    $this->skippedCount += 1;

                    $results[$index]['skipped'] = true;
                }
            }
        }

        $this->log = $results;
    }

    public function isSuccess()
    {
        return 0 === $this->skippedCount && 0 === $this->failureCount;
    }

    public function isWarning()
    {
        return 0 < $this->skippedCount && 0 === $this->failureCount;
    }

    public function isFailure()
    {
        return 0 < $this->failureCount;
    }

    public function getMessage()
    {
        if ($this->isSuccess()) {
            return sprintf('Successfully ran %d tests.', $this->testCount);
        } elseif ($this->isWarning()) {
            return sprintf('No failures, but skipped %d of %d tests.', $this->skippedCount, $this->testCount);
        } else {
            return sprintf('Failed %d of %d tests.', $this->failureCount, $this->testCount);
        }
    }

    private function parseJsonData()
    {
        return json_decode(
            '[' . str_replace('}{', '},{', file_get_contents($this->file)) . ']',
            true
        );
    }

    private function testWasSkipped(array $logEvent)
    {
        return isset($logEvent['trace'][0]) &&
            isset($logEvent['trace'][0]['function']) &&
            'markTestSkipped' === $logEvent['trace'][0]['function'];
    }
}
