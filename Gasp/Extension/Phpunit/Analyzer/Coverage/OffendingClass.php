<?php

/**
 * Gasp
 *
 * @link https://github.com/griffbrad/gasp
 */

namespace Gasp\Extension\Phpunit\Analyzer\Coverage;

use Gasp\SetOptions;

/**
 * This class helps to track information about classes that the coverage
 * analyzer found to be beneath the specified coverage threshold.
 */
class OffendingClass
{
    use SetOptions;

    /**
     * The name of the offending class from the coverage analysis.
     *
     * @var string
     */
    private $className;

    /**
     * The total number of statements in the offending class.
     *
     * @var int
     */
    private $totalStatements;

    /**
     * The number of statements covered by tests in the offending class.
     *
     * @var int
     */
    private $coveredStatements;

    /**
     * The overall code coverage calculated for the offending class.
     *
     * @var float
     */
    private $coverage;

    /**
     * Set multiple options, specifying the details about the offending class.
     *
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        $this->setOptions($options);
    }

    /**
     * Set the name of the offending class.
     *
     * @param $className
     * @return $this
     */
    public function setClassName($className)
    {
        $this->className = $className;

        return $this;
    }

    /**
     * Get the name of the offending class.
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * Set the total number of statements in the offending class.
     *
     * @param int $totalStatements
     * @return $this
     */
    public function setTotalStatements($totalStatements)
    {
        $this->totalStatements = $totalStatements;

        return $this;
    }

    /**
     * Get the total number of statements in the offending class.
     *
     * @return int
     */
    public function getTotalStatements()
    {
        return $this->totalStatements;
    }

    /**
     * Set the number of statements covered by tests in the offending class.
     *
     * @param int $coveredStatements
     * @return $this
     */
    public function setCoveredStatements($coveredStatements)
    {
        $this->coveredStatements = $coveredStatements;

        return $this;
    }

    /**
     * Get the number of statements covered by tests in the offending class.
     *
     * @return int
     */
    public function getCoveredStatements()
    {
        return $this->coveredStatements;
    }

    /**
     * Set the calculated coverage for the offending class.
     *
     * @param float $coverage
     * @return $this
     */
    public function setCoverage($coverage)
    {
        $this->coverage = $coverage;

        return $this;
    }

    /**
     * Get the calculated coverage for the offending class.
     *
     * @return float
     */
    public function getCoverage()
    {
        return $this->coverage;
    }

    /**
     * We score each class based upon the number of uncovered statements.
     *
     * @return float
     */
    public function getWeightedScore()
    {
        return $this->totalStatements - $this->coveredStatements;
    }
}
