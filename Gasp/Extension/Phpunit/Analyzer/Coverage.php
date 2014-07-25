<?php

namespace Gasp\Extension\Phpunit\Analyzer;

class Coverage implements AnalyzerInterface
{
    private $file;

    private $threshold;

    private $coverage;

    private $badClasses = array();

    public function setFile($file)
    {
        $this->file = $file;
    }

    public function deleteFile()
    {
        if (file_exists($this->file)) {
            unlink($this->file);
        }
    }

    public function setThreshold($threshold)
    {
        $this->threshold = $threshold;

        return $this;
    }

    public function getCmdArg()
    {
        if (!$this->hasXdebug()) {
            return '';
        } else {
            return sprintf('--coverage-clover %s', escapeshellarg($this->file));
        }
    }

    public function analyze()
    {
        if (!$this->hasXdebug()) {
            return;
        }

        // Reset badClasses in case analyze is called multiple times
        $this->badClasses = array();

        $results  = simplexml_load_file($this->file);
        $classes  = $results->xpath('/coverage/project/package/file/class');
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

            if ($this->isBeneathThreshold($classCoverage)) {
                $out['badClasses'][] = $class['namespace'] . '\\' . $class['name'];
            }
        }

        $this->coverage = array_sum($coverage) / count($coverage);
    }

    public function isSuccess()
    {
        return $this->hasXdebug() && !$this->isBeneathThreshold($this->coverage);
    }

    public function isWarning()
    {
        return !$this->hasXdebug() || $this->isBeneathThreshold($this->coverage);
    }

    public function isFailure()
    {
        return false;
    }

    public function getMessage()
    {
        if (!$this->hasXdebug()) {
            return 'Could not calculate coverage because xdebug is not enabled.';
        } elseif ($this->isBeneathThreshold($this->coverage)) {
            return sprintf(
                'Covered only %d%% of statements.  %d%% needed.',
                number_format($this->coverage * 100, 2),
                number_format($this->threshold * 100, 2)
            );
        } else {
            return sprintf('Covered %d%% of statements.', number_format($this->coverage * 100, 2));
        }
    }

    protected function hasXdebug()
    {
        return extension_loaded('xdebug');
    }

    protected function isBeneathThreshold($coverage)
    {
        return $this->threshold && $coverage < $this->threshold;
    }
}
