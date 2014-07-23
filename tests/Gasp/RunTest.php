<?php

namespace Gasp;

class RunTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Run
     */
    private $run;

    public function setUp()
    {
        $this->run = new Run();
    }

    public function testCanUseACustomWorkingDirectory()
    {
        $run = new Run(new ClassMap(), __DIR__);
        $this->assertEquals(__DIR__, $run->getWorkingDirectory());
    }

    public function testResultOfGetcwdCallIsUsedForWorkingDirectoryByDefault()
    {
        $this->assertEquals(getcwd(), $this->run->getWorkingDirectory());
    }

    /**
     * @expectedException \Gasp\Exception
     */
    public function testPassingANonExistentWorkingDirectoryThrowsException()
    {
        new Run(new ClassMap(), __DIR__ . '/fafafafa');
    }

    /**
     * @expectedException \Gasp\Exception
     */
    public function testPassingAWorkingDirectoryThatIsNotADirectoryThrowsException()
    {
        new Run(new ClassMap(), __FILE__);
    }

    public function testCanDefineACustomDefaultClassMapViaConstructor()
    {
        $classMap = new ClassMap();

        // If our custom class map is used, exec() will return Lint instead of Exec
        $classMap->register('exec', '\Gasp\Task\Lint');

        $run = new Run($classMap);
        $this->assertInstanceOf('\Gasp\Task\Lint', $run->exec());
    }

    public function testsAllValidGaspFilesAreAccepted()
    {
        $path = __DIR__ . '/find-gaspfile-tests';

        $files =array(
            'lower-g-no-php'   => 'gaspfile',
            'lower-g-with-php' => 'gaspfile.php',
            'upper-g-no-php'   => 'Gaspfile',
            'upper-g-with-php' => 'Gaspfile.php'
        );

        foreach ($files as $subfolder => $file) {
            $run = new Run(new ClassMap(), $path . '/' . $subfolder);

            // Doing strtolower() here because user may be on case-insensitive filesystem.
            // Important part is that the file is accepted and found, not the case of the result.
            $this->assertEquals(
                strtolower($path . '/' . $subfolder . '/' . $file),
                strtolower($run->findGaspfile())
            );
        }
    }

    /**
     * @expectedException \Gasp\Exception
     */
    public function testMissingGaspFileThrowsException()
    {
        $run = new Run(new ClassMap(), __DIR__);
        $run->findGaspfile();
    }

    public function testWillRunTaskSpecifiedInFirstCliArgument()
    {
        $this->run->task(
            'custom',
            function () {
                return $this->run->result()
                    ->setMessage('CUSTOM_TASK_RESULT');
            }
        );

        $serverVars = array('argv' => array('cmd', 'custom'));
        $this->run->setServerVars($serverVars);

        ob_start();
        $this->run->run();
        $this->assertContains('CUSTOM_TASK_RESULT', ob_get_clean());
    }

    public function testRunningNativeTaskWillCallItsValidateMethodFirst()
    {
        $task = $this->getMock(
            '\Gasp\Task\Lint',
            array('validate', 'run'),
            array()
        );

        $task->expects($this->any())
            ->method('validate');

        /* @var $task \Gasp\Task\Lint */
        $task->setGasp($this->run);

        $this->run->runTask($task);
    }

    /**
     * @expectedException \Gasp\Exception
     */
    public function testDefiningCustomTaskWithInvalidTypeThrowsException()
    {
        $this->run->task('invalid_task_type', 1);
    }

    public function testDefiningCustomTaskWithStringServesAsAnAlias()
    {
        $classMap = new ClassMap();
        $classMap->register('dummy', '\Gasp\Task\Dummy');

        $run = new Run($classMap);
        $run->task('my_alias', 'dummy');

        $serverVars = array('argv' => array('cmd', 'my_alias'));
        $run->setServerVars($serverVars);

        ob_start();
        $run->run();
        $this->assertContains('DUMMY_TASK', ob_get_clean());
    }

    /**
     * @expectedException \Gasp\Exception
     */
    public function testIfATaskDoesNotReturnAResultAnExceptionIsThrown()
    {
        $this->run->task(
            'custom',
            function () {
                return null;
            }
        );

        $serverVars = array('argv' => array('cmd', 'custom'));
        $this->run->setServerVars($serverVars);
        $this->run->run();
    }

    public function testWillAttemptToRunDefaultTaskIfNoneIsSpecified()
    {
        $classMap = new ClassMap();
        $classMap->register('dummy', '\Gasp\Task\Dummy');

        $run = new Run($classMap);
        $run->task('default', 'dummy');

        $serverVars = array('argv' => array());
        $run->setServerVars($serverVars);

        ob_start();
        $run->run();
        $this->assertContains('DUMMY_TASK', ob_get_clean());
    }

    public function testWillUseCoreServerSuperGlobalByDefault()
    {
        $this->assertEquals($_SERVER, $this->run->getServerVars());
    }

    public function testIncludingDotInTaskNameResolvesToClassMapNameAndThenTaskName()
    {
        $this->assertInstanceOf('\Gasp\Task\Lint', $this->run->getTaskByName('default.lint'));
    }

    public function testCanDefineCustomTaskUsingClosure()
    {
        $this->run->task(
            'custom',
            function () {
                return $this->run->result()
                    ->setMessage('CUSTOM_TASK_RESULT');
            }
        );

        $this->assertContains('CUSTOM_TASK_RESULT', $this->run->runTaskByName('custom')->display());
    }

    public function testResultMethodReturnsANewResultInstance()
    {
        $this->assertInstanceOf('\Gasp\Result', $this->run->result());

        $a = $this->run->result();
        $b = $this->run->result();

        $this->assertNotEquals(spl_object_hash($a), spl_object_hash($b));
    }

    public function testAggregateMethodReturnsANewAggregateResultInstance()
    {
        $this->assertInstanceOf('\Gasp\Result\Aggregate', $this->run->aggregate());

        $a = $this->run->aggregate();
        $b = $this->run->aggregate();

        $this->assertNotEquals(spl_object_hash($a), spl_object_hash($b));
    }

    /**
     * @expectedException \Gasp\Exception
     */
    public function testCallingRunTaskWithInvalidTypeThrowsException()
    {
        $this->run->runTask(1);
    }

    public function testCanDefineCustomTaskThatBundlesUpSeveralOthersInAnArray()
    {
        $classMap = new ClassMap();
        $classMap->register('dummy', '\Gasp\Task\Dummy');

        $run = new Run($classMap);
        $run->task('dummy-alias', 'dummy');
        $run->task('bundle', ['dummy', 'dummy-alias']);

        $this->assertInstanceOf('\Gasp\Result\Aggregate', $run->runTaskByName('bundle'));
    }
}
