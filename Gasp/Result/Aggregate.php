<?php

/**
 * Gasp
 *
 * @link https://github.com/griffbrad/gasp
 */

namespace Gasp\Result;

use Gasp\Result;

/**
 * This class allows you to bundle up the results of several tasks.  Just
 * call the addResult() method to drop in additional results.
 */
class Aggregate implements ResultInterface
{
    /**
     * The results that have been added.
     *
     * @var array
     */
    private $results = array();

    /**
     * Just a shortcut to addResult().
     *
     * @param ResultInterface $result
     * @return $this
     */
    public function add(ResultInterface $result)
    {
        return $this->addResult($result);
    }

    /**
     * Add a new result to this aggregate.
     *
     * @param ResultInterface $result
     * @return $this
     */
    public function addResult(ResultInterface $result)
    {
        $this->results[] = $result;

        return $this;
    }

    /**
     * Display all the added results.
     *
     * @return string
     */
    public function display()
    {
        $output = '';

        /* @var $result ResultInterface */
        foreach ($this->results as $result) {
            $output .= $result->display();
        }

        return $output;
    }

    /**
     * Check to see if the aggregate was successful.  Only returns true if all
     * results were successful.
     *
     * @return bool
     */
    public function isSuccess()
    {
        /* @var $result ResultInterface */
        foreach ($this->results as $result) {
            if (!$result->isSuccess()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check to see if this aggregate was a warning.  Only returns true if all
     * results were warnings.
     *
     * @return bool
     */
    public function isWarning()
    {
        /* @var $result ResultInterface */
        foreach ($this->results as $result) {
            if (!$result->isWarning()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check to see if this aggregate was a failure.  Will return true if _any_
     * result was a failure.
     *
     * @return bool
     */
    public function isFailure()
    {
        /* @var $result ResultInterface */
        foreach ($this->results as $result) {
            if (!$result->isFailure()) {
                return true;
            }
        }

        return false;
    }
}
