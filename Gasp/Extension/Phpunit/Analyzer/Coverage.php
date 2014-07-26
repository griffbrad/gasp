<?php

namespace Gasp\Extension\Phpunit\Analyzer;

use Gasp\Extension\Phpunit\Analyzer\Coverage\OffendingClass;
use Gasp\Render\Table;
use Gasp\Terminal;

class Coverage implements AnalyzerInterface
{
    private $file;

    private $threshold;

    private $coverage;

    private $offendingClasses = array();

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

        // Reset offendingClasses in case analyze is called multiple times
        $this->offendingClasses = array();

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

            if ($totalStatements && $this->isBeneathThreshold($classCoverage)) {
                $this->offendingClasses[] = new OffendingClass(
                    array(
                        'className'         => $class['namespace'] . '\\' . $class['name'],
                        'totalStatements'   => $totalStatements,
                        'coveredStatements' => $coveredStatements,
                        'coverage'          => $classCoverage
                    )
                );
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
                'Covered only %s of statements.  %s needed.',
                $this->formatPercentage($this->coverage),
                $this->formatPercentage($this->threshold)
            );
        } else {
            return sprintf('Covered %d%% of statements.', number_format($this->coverage * 100, 2));
        }
    }

    public function getOutput(Terminal $terminal)
    {
        $table = new Table();

        $table->setHeaders(['Classes lacking sufficient coverage', 'Coverage', 'Statements']);

        /* @var $class OffendingClass */
        foreach ($this->getSortedOffendingClasses() as $class) {
            $table->addRow(
                array(
                    $class->getClassName(),
                    $this->formatPercentage($class->getCoverage()),
                    $class->getTotalStatements()
                )
            );
        }

        return $table->render();
    }

    protected function getSortedOffendingClasses()
    {
        $classes = $this->offendingClasses;

        usort(
            $classes,
            function ($a, $b) {
                /* @var $a OffendingClass */
                /* @var $b OffendingClass */

                if ($a->getWeightedScore() === $b->getWeightedScore()) {
                    return 0;
                }

                return ($a->getWeightedScore() < $b->getWeightedScore()) ? 1 : -1;
            }
        );

        return $classes;
    }

    protected function formatPercentage($value)
    {
        return preg_replace('/\.00$/', '', number_format($value * 100, 2)) . '%';
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
