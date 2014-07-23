<?php

namespace Gasp\Task;

use Gasp\Run;

class ExecTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Exec
     */
    private $exec;

    public function setUp()
    {
        $gasp = new Run();

        $this->exec = new Exec();
        $this->exec->setGasp($gasp);
    }

    /**
     * @expectedException \Gasp\Exception
     */
    public function testNotSettingACmdThrowsAnException()
    {
        $this->exec->validate();
    }

    public function testSuccessOrFailureIsBasedUponCmdExitStatus()
    {
        $result = $this->exec
            ->setCmd("bash -c 'exit 0'")
            ->run();

        $this->assertTrue($result->isSuccess());

        $result = $this->exec
            ->setCmd("bash -c 'exit 1'")
            ->run();

        $this->assertTrue($result->isFailure());
    }

    public function testCommandOutputIsIncludedInResult()
    {
        $result = $this->exec
            ->setCmd("bash -c 'echo TEST_OUTPUT'")
            ->run();

        $this->assertContains('TEST_OUTPUT', $result->getOutput());
    }
}
