<?php

namespace Gasp;

class Validate
{
    /**
     * Check that the given executable is defined, exists, and is executable.
     *
     * @param $executable
     * @param $exceptionClass
     * @throw \Gasp\Exception
     */
    public static function checkExecutable($executable, $exceptionClass)
    {
        if (!$executable) {
            throw new $exceptionClass('No command defined.');
        }

        if (!file_exists($executable)) {
            throw new $exceptionClass("Command not found at: {$executable}");
        }

        if (!is_executable($executable)) {
            throw new $exceptionClass('Command is not executable.');
        }
    }
}
