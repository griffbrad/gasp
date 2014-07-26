<?php

/**
 * Gasp
 *
 * @link https://github.com/griffbrad/gasp
 */

namespace Gasp\Extension\Phpunit\Task;

use Gasp\Exception;
use Gasp\Extension\Phpunit\Analyzer\AnalyzerInterface;
use Gasp\Extension\Phpunit\Analyzer\Coverage as CoverageAnalyzer;
use Gasp\Extension\Phpunit\Analyzer\Log as LogAnalyzer;
use Gasp\Result;
use Gasp\Task\TaskAbstract;

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
class RunTests extends TaskAbstract
{
    private $analyzers = array();

    public function __construct()
    {
        $this->analyzers = array(
            'log'      => new LogAnalyzer(),
            'coverage' => new CoverageAnalyzer()
        );
    }

    /**
     * Run phpunit.
     *
     * @return \Gasp\Result\ResultInterface
     */
    public function run()
    {
        $cwd = $this->gasp->getWorkingDirectory();

        /* @var $analyzer AnalyzerInterface */
        foreach ($this->analyzers as $name => $analyzer) {
            $analyzer->setFile($cwd . '/.gasp-phpunit-' . $name . '-' . microtime(true));
        }

        $this->execCmd();

        /* @var $coverage CoverageAnalyzer */
        $coverage = $this->analyzers['coverage'];
        $coverage->setThreshold($this->classMap->getCoverageThreshold());

        /* @var $analyzer AnalyzerInterface */
        foreach ($this->analyzers as $analyzer) {
            $analyzer->analyze();
            $analyzer->deleteFile();
        }

        $taskResult = $this->gasp->result()
            ->setMessage($this->assembleMessage())
            ->setOutput($this->assembleOutput());

        if ($this->analyzersHaveFailure()) {
            $taskResult->setStatus(Result::FAIL);
        } elseif ($this->analyzersHaveWarning()) {
            $taskResult->setStatus(Result::WARN);
        } else {
            $taskResult->setStatus(Result::SUCCESS);
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
        if (!$this->classMap->getPhpunit()) {
            throw new Exception('No phpunit command defined.');
        }

        if (!file_exists($this->classMap->getPhpunit())) {
            throw new Exception("php command not found at: {$this->classMap->getPhpunit()}");
        }

        if (!is_executable($this->classMap->getPhpunit())) {
            throw new Exception('phpunit command is not executable.');
        }

        if (null === $this->classMap->getPath()) {
            throw new Exception('Path must be set before running phpunit.');
        }
    }

    /**
     * @return Result
     */
    protected function execCmd()
    {
        $config = $this->classMap->getConfiguration();
        $args   = [];

        // phpunit.xml configuration
        $args[] = (null !== $config ? '-c ' . escapeshellarg($config) : '');

        /* @var $analyzer AnalyzerInterface */
        foreach ($this->analyzers as $analyzer) {
            $arg = $analyzer->getCmdArg();

            if ($arg) {
                $args[] = $arg;
            }
        }

        // Actual path to tests
        $args[] = escapeshellarg($this->classMap->getPath());

        $cmd = $this->classMap->getPhpunit() . ' ' . implode(' ', $args);

        return $this->gasp->exec()
            ->setCmd($cmd)
            ->run();
    }

    protected function assembleMessage()
    {
        $message = [];

        /* @var $analyzer AnalyzerInterface */
        foreach ($this->analyzers as $analyzer) {
            $message[] = $analyzer->getMessage();
        }

        return implode(' ', $message);
    }

    protected function assembleOutput()
    {
        $output = [];

        /* @var $analyzer AnalyzerInterface */
        foreach ($this->analyzers as $analyzer) {
            $analyzerOutput = $analyzer->getOutput($this->gasp->terminal());

            if ($analyzerOutput) {
                $output[] = $analyzerOutput;
            }
        }

        return implode(PHP_EOL . PHP_EOL, $output);
    }

    protected function analyzersHaveFailure()
    {
        /* @var $analyzer AnalyzerInterface */
        foreach ($this->analyzers as $analyzer) {
            if ($analyzer->isFailure()) {
                return true;
            }
        }

        return false;
    }

    protected function analyzersHaveWarning()
    {
        /* @var $analyzer AnalyzerInterface */
        foreach ($this->analyzers as $analyzer) {
            if ($analyzer->isWarning()) {
                return true;
            }
        }

        return false;
    }
}
