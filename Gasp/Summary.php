<?php

namespace Gasp;

class Summary
{
    private $result;

    private $icons = array(
        Result::SUCCESS => '✓',
        Result::WARN    => '✵',
        Result::FAIL    => '✗'
    );

    public function __construct(Result $result)
    {
        $this->result = $result;
    }

    public function __toString()
    {
        return sprintf(
            "%s %s\n",
            $this->icons[$this->result->getStatus()],
            $this->result->getMessage()
        );
    }
}
