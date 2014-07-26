<?php

namespace Gasp\Render;

use Gasp\Terminal;

class Header
{
    public function __construct(Terminal $terminal)
    {
        $this->terminal = $terminal;
    }

    public function render($text, $subhead)
    {
        $out = $this->terminal->token("\033[4m") . $text . $this->terminal->token("\033[0m") . PHP_EOL;

        if ($subhead) {
            $out .= $subhead . PHP_EOL;
        }

        return $out;
    }
}
