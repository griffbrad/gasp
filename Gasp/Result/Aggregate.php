<?php

namespace Gasp\Result;

use Gasp\Result;

class Aggregate implements ResultInterface
{
    private $results = array();

    public function addResult(Result $result)
    {
        $this->results[] = $result;

        return $this;
    }

    public function display()
    {
        $output = '';

        /* @var $result Result */
        foreach ($this->results as $result) {
            $output .= $result->display();
        }

        return $output;
    }

    public function isSuccess()
    {
        /* @var $result Result */
        foreach ($this->results as $result) {
            if (!$result->isSuccess()) {
                return false;
            }
        }

        return true;
    }

    public function isWarning()
    {
        /* @var $result Result */
        foreach ($this->results as $result) {
            if (!$result->isWarning()) {
                return false;
            }
        }

        return true;
    }

    public function isFailure()
    {
        /* @var $result Result */
        foreach ($this->results as $result) {
            if (!$result->isFailure()) {
                return true;
            }
        }

        return false;
    }
}
