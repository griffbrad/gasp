<?php

namespace Gasp\Task;

use Gasp\Run;

class WatchTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Watch
     */
    private $watch;

    public function setUp()
    {
        if (false === stripos(PHP_OS, 'linux')) {
            $this->markTestSkipped('inotify is only available on Linux');
        }

        $gasp = new Run();

        $this->watch = new Watch();
        $this->watch->setGasp($gasp);
    }

    public function testCanAddATasksAndItsAssociatedPaths()
    {
        $this->watch->addTask('dummy', [__DIR__]);
    }
}
