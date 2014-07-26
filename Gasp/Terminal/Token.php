<?php

namespace Gasp\Terminal;

use Gasp\Terminal;

class Token
{
    private $terminal;

    private $content;

    public function __construct(Terminal $terminal, $content)
    {
        $this->terminal = $terminal;
        $this->content  = $content;
    }

    public function __toString()
    {
        if ($this->terminal->isInteractive()) {
            return $this->content;
        } else {
            return '';
        }
    }
}
