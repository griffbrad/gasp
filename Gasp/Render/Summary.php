<?php

/**
 * Gasp
 *
 * @link https://github.com/griffbrad/gasp
 */

namespace Gasp\Render;

use Gasp\Result;

/**
 * This class displays a summary for a result, including its status and message.
 */
class Summary
{
    /**
     * The result the summary pertains to.
     *
     * @var Result
     */
    private $result;

    /**
     * Some UTF-8 characters that can be used to indicate result status.
     *
     * @var array
     */
    private $icons = array(
        Result::SUCCESS => '✓',
        Result::WARN    => '✵',
        Result::FAIL    => '✗'
    );

    /**
     * Bash console colors for the 3 statuses so they're easier to spot.
     *
     * @var array
     */
    private $colors = array(
        Result::SUCCESS => array('set' => 32, 'unset' => 39),
        Result::WARN    => array('set' => 33, 'unset' => 39),
        Result::FAIL    => array('set' => 31, 'unset' => 39)
    );

    /**
     * Provide the result for which we need to display a summary.
     *
     * @param Result $result
     */
    public function __construct(Result $result)
    {
        $this->result = $result;
    }

    /**
     * Get the actual summary string.
     *
     * @return string
     */
    public function __toString()
    {
        $message = sprintf(
            "%s %s",
            $this->icons[$this->result->getStatus()],
            $this->result->getMessage()
        );

        $color    = $this->colors[$this->result->getStatus()];
        $terminal = $this->result->getTerminal();

        return $terminal->token("\033[{$color['set']}m") .
            $message .
            $terminal->token("\033[{$color['unset']}m") .
            PHP_EOL;
    }
}
