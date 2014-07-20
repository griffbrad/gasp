<?php

/**
 * Gasp
 *
 * @link https://github.com/griffbrad/gasp
 */

namespace Gasp;

/**
 * This class displays a summary for a result, including its status and message.
 * This class may be enhanced to included console colors, etc.
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
        return sprintf(
            "%s %s\n",
            $this->icons[$this->result->getStatus()],
            $this->result->getMessage()
        );
    }
}
