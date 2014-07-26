<?php

/**
 * Gasp
 *
 * @link https://github.com/griffbrad/gasp
 */

namespace Gasp\Result;

use Gasp\Run;

/**
 * This interface applies to both normal and aggregate results.
 */
interface ResultInterface
{
    /**
     * Generate the output for the result.
     *
     * @return string
     */
    public function display();

    /**
     * Check to see if it was a success.
     *
     * @return mixed
     */
    public function isSuccess();

    /**
     * Check to see if it was a failure.
     *
     * @return mixed
     */
    public function isFailure();

    /**
     * Check to see if it generated a warning.
     *
     * @return mixed
     */
    public function isWarning();
}
