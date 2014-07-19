<?php

namespace Gasp\Task;

use Gasp\Run;

interface TaskInterface
{
    public function setGasp(Run $gasp);

    /**
     * @return \Gasp\Result
     */
    public function run();

    public function validate();
}
