<?php

namespace Gasp;

use Gasp\Terminal\Token;

class Terminal
{
    private $isInteractive;

    public function token($content)
    {
        return new Token($this, $content);
    }

    public function isInteractive()
    {
        if (null === $this->isInteractive) {
            $this->isInteractive = (
                'cli' === php_sapi_name() &&
                (
                    (function_exists('posix_isatty') && @posix_isatty(STDOUT)) ||
                    'ON' === getenv('ConEmuANSI') ||
                    false !== getenv('ANSICON')
                )
            );
        }

        return $this->isInteractive;
    }
}
