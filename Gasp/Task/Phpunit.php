<?php

/**
 * Gasp
 *
 * @link https://github.com/griffbrad/gasp
 */

namespace Gasp\Task;

use Gasp\Exception;
use Gasp\Result;

/**
 * Run phpunit on a folder of tests.  You'll need to supply two pieces of
 * information:
 *
 * 1) setPhpunit(): The location of the phpunit command.
 *
 * 2) setPath(): The folder where tests can be found.
 *
 * Example gaspfile definition:
 *
 * <code>
 * $gasp->phpunit()
 *     ->setPhpunit('./vendor/bin/phpunit')
 *     ->addPath('tests');
 * </code>
 *
 * Example usage:
 *
 * <code>
 * ./vendor/bin/gasp phpunit
 * </code>
 */
class Phpunit extends TaskAbstract
{
    /**
     * The phpunit executable that should be used to run the tests.
     *
     * @var string
     */
    private $phpunit;

    /**
     * The path where tests can be found.
     *
     * @var string
     */
    private $path;

    /**
     * The path to your phpunit.xml configuration.  If not specified, we'll
     * attempt to find it in your tests path.
     *
     * @var string
     */
    private $configuration;

    /**
     * The threshold you want to require for the code coverage of your tests.
     *
     * @var int
     */
    private $coverageThreshold;

    /**
     * Set the location of the phpunit executable.
     *
     * @param string $phpunit
     * @return $this
     */
    public function setPhpunit($phpunit)
    {
        $this->phpunit = $phpunit;

        return $this;
    }

    /**
     * Set the path where tests can be found.
     *
     * @param string $path
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = rtrim($path, '/');

        return $this;
    }

    /**
     * Get all the paths assigned to this task.
     *
     * @return array
     */
    public function getPath()
    {
        return $this->path;
    }

    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;

        return $this;
    }

    public function getConfiguration()
    {
        if (null === $this->configuration) {
            $default = $this->path . '/phpunit.xml';

            if (file_exists($default)) {
                $this->configuration = $default;
            }
        }

        return $this->configuration;
    }

    /**
     * Set the code coverage threshold.  If coverage is below this amount, a
     * warning will be generated.
     *
     * @param string $coverageThreshold
     * @return $this
     */
    public function setCoverageThreshold($coverageThreshold)
    {
        if ($coverageThreshold && 1 < $coverageThreshold) {
            $coverageThreshold = $coverageThreshold / 100;
        }

        $this->coverageThreshold = $coverageThreshold;

        return $this;
    }

    /**
     * Run phpunit.
     *
     * @return \Gasp\Result\ResultInterface
     */
    public function run()
    {
        $logFile      = $this->gasp->getWorkingDirectory() . '/.gasp-phpunit-log-' . microtime(true);
        $coverageFile = $this->gasp->getWorkingDirectory() . '/.gasp-phpunit-coverage-' . microtime(true);

        $cmd = sprintf(
            '%s %s --log-json %s --coverage-clover %s %s',
            $this->phpunit,
            (null !== $this->getConfiguration() ? '-c ' . escapeshellarg($this->getConfiguration()) : ''),
            escapeshellarg($logFile),
            escapeshellarg($coverageFile),
            escapeshellarg($this->path)
        );

        /* @var $execResult Result */
        $execResult = $this->gasp->exec()
            ->setCmd($cmd)
            ->run();

        list($testCount, $failures, $skipped, $log) = $this->processLogFile($logFile);

        list($coverage, $badFiles) = $this->processCoverageFile($coverageFile);

        unlink($logFile);
        unlink($coverageFile);

        $taskResult = $this->gasp->result();

        if ($failures) {
            $taskResult
                ->setStatus(Result::FAIL)
                ->setMessage(sprintf('Failed %d out of %d tests.', $failures, $testCount));
        } elseif ($skipped || count($badFiles) || ($this->coverageThreshold && $this->coverageThreshold < $coverage)) {
            $taskResult
                ->setStatus(Result::WARN)
                ->setMessage($this->assembleWarnMessage($skipped, $testCount, $coverage, $badFiles))
                ->setOutput($this->getOutputForSkippedTests($log));
        } elseif ($execResult->isSuccess()) {
            $taskResult
                ->setStatus(Result::SUCCESS)
                ->setMessage($this->assembleSuccessMessage($testCount, $coverage));
        }

        return $taskResult;
    }

    /**
     * Ensure phpunit is set and executable and that a path has been set before
     * running.
     *
     * @throws \Gasp\Exception
     */
    public function validate()
    {
        if (!$this->phpunit) {
            throw new Exception('No phpunit command defined.');
        }

        if (!file_exists($this->phpunit)) {
            throw new Exception("php command not found at: {$this->phpunit}");
        }

        if (!is_executable($this->phpunit)) {
            throw new Exception('phpunit command is not executable.');
        }

        if (null === $this->path) {
            throw new Exception('Path must be set before running phpunit.');
        }
    }

    protected function processLogFile($jsonLogFile)
    {
        $json    = '[' . str_replace('}{', '},{', file_get_contents($jsonLogFile)) . ']';
        $results = json_decode($json, true);
        $out     = ['count' => 0, 'failures' => 0, 'skipped' => 0, 'log' => null];

        foreach ($results as $index => $event) {
            if ('test' !== $event['event']) {
                continue;
            }

            // Adding "skipped" key to all events so we can more easily track them
            $results[$index]['skipped'] = false;

            $out['count'] += 1;

            if ('error' === $event['status']) {
                $trace = $event['trace'];

                if (!isset($trace[0]) || !isset($trace[0]['function']) || 'markTestSkipped' !== $trace[0]['function']) {
                    $out['failures'] += 1;
                } else {
                    $out['skipped'] += 1;

                    $results[$index]['skipped'] = true;
                }
            }
        }

        $out['log'] = $results;

        return array_values($out);
    }

    protected function processCoverageFile($coverageFile)
    {
        $results  = simplexml_load_file($coverageFile);
        $classes  = $results->xpath('/coverage/project/package/file/class');
        $out      = ['coverage' => 0, 'badClasses' => []];
        $coverage = [];

        foreach ($classes as $class) {
            $totalStatements   = (int) $class->metrics['statements'];
            $coveredStatements = (int) $class->metrics['coveredstatements'];

            if (!$totalStatements) {
                $classCoverage = 0;
            } else {
                $classCoverage = $coveredStatements / $totalStatements;
            }

            $coverage[] = $classCoverage;

            if ($this->coverageThreshold && $this->coverageThreshold > $classCoverage) {
                $out['badClasses'][] = $class['namespace'] . '\\' . $class['name'];
            }
        }

        $out['coverage'] = array_sum($coverage) / count($coverage);

        return array_values($out);
    }

    protected function getOutputForSkippedTests(array $logData)
    {
        $out = '';

        foreach ($logData as $event) {
            if ('test' === $event['event'] && $event['skipped']) {
                if ($event['message']) {
                    $message = $event['message'];
                } else {
                    $message = 'No message provided.  Please specify why test was skipped.';
                }

                $out .= "Skipped {$event['test']}: {$message}" . PHP_EOL;
            }
        }

        return $out;
    }

    private function assembleSuccessMessage($testCount, $coverage)
    {
        $message = sprintf(
            'Successfully ran %d tests.  ',
            $testCount
        );

        $message .= $this->assembleCoverageMessage($coverage);

        return $message;
    }

    private function assembleWarnMessage($skipped, $testCount, $coverage, array $badFiles)
    {
        $message = '';

        if ($skipped) {
            $message .= sprintf('No failures, but skipped %d of %d tests.  ', $skipped, $testCount);
        } else {
            $message .= sprintf('Successfully ran %d tests.  ', $testCount);
        }

        if (!$this->coverageThreshold || $coverage >= $this->coverageThreshold) {
            $message .= $this->assembleCoverageMessage($coverage);
        } else {
            $message .= sprintf(
                'Covered only %d%% of statements.  %d%% needed.',
                number_format($coverage * 100, 2),
                number_format($this->coverageThreshold * 100, 2)
            );
        }

        return $message;
    }

    private function assembleCoverageMessage($coverage)
    {
        return sprintf('Covered %d%% of statements.', number_format($coverage * 100, 2));
    }
}
