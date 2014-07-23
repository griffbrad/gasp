<?php

// No namespace provided

class MyClass
{
    public function init()
    {
        // Multiple lines jammed together
        $test = 1; $foo = 2;

        // Long line
        echo "This is a very long line that will cause an error {$test} with {$foo} stuff in it.  Just typing.  Typing.  Typing.";
    }
}
