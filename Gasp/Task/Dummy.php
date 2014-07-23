<?php

/**
 * Gasp
 *
 * @link https://github.com/griffbrad/gasp
 */

namespace Gasp\Task;

use Gasp\Result;

/**
 * A dummy task that returns a consistent result.  Sometimes useful in testing.
 */
class Dummy extends TaskAbstract
{
    public function run()
    {
        return $this->gasp->result()
            ->setStatus(Result::SUCCESS)
            ->setMessage('DUMMY_TASK');
    }

    public function validate()
    {

    }
}
