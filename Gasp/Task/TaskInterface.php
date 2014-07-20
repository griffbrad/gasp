<?php

/**
 * Gasp
 *
 * @link https://github.com/griffbrad/gasp
 */

namespace Gasp\Task;

use Gasp\Exception;
use Gasp\Run;

/**
 * This is the interface all gasp tasks must conform to.  It provides the
 * main run() method for the task, requires tasks to validate their parameters
 * before running and ensures that a references to the gasp Run object itself
 * is available to the task.
 */
interface TaskInterface
{
    /**
     * Provide a reference to the Run object so that other tasks can be run,
     * etc.
     *
     * @param Run $gasp
     * @return TaskInterface
     */
    public function setGasp(Run $gasp);

    /**
     * Run the task and return a ResultInterface object.
     *
     * @return \Gasp\Result\ResultInterface
     */
    public function run();

    /**
     * Check that all the needed params have been set and are valid prior to
     * continuing with run().
     *
     * @throws Exception
     */
    public function validate();
}
