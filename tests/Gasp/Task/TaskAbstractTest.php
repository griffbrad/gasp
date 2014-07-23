<?php

namespace Gasp\Task;

use Gasp\Run;

class TaskAbstractTest extends \PHPUnit_Framework_TestCase
{
    public function testCanSetGaspInstanceOnTask()
    {
        $gasp = new Run();
        $task = new Lint();
        $task->setGasp($gasp);
    }

    public function testCanSetMultipleOptionsViaConstructor()
    {
        $gasp = new Run();
        $task = new Lint(['gasp' => $gasp, 'paths' => [__DIR__]]);

        $this->assertEquals([__DIR__], $task->getPaths());
    }

    public function testCanSetMultipleOptionsViaSetOptionsMethod()
    {
        $gasp = new Run();
        $task = new Lint();

        $task->setOptions(['gasp' => $gasp, 'paths' => [__DIR__]]);

        $this->assertEquals([__DIR__], $task->getPaths());
    }

    /**
     * @expectedException \Gasp\Exception
     */
    public function testAttemptingToSetInvalidOptionThrowsException()
    {
        $task = new Lint();

        $task->setOptions(['fafafafa' => true]);
    }
}
