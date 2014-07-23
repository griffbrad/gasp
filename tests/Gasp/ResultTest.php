<?php

namespace Gasp;

class ResultTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Result
     */
    private $result;

    public function setUp()
    {
        $this->result = new Result();
    }

    /**
     * @expectedException \Gasp\Exception
     */
    public function testSettingInvalidStatusThrowsException()
    {
        $this->result->setStatus('fafafafa');
    }

    public function testCommonAliasesForFailedStatusAreAccepted()
    {
        $this->result->setStatus('failed');
        $this->assertTrue($this->result->isFailure());

        $this->result->setStatus('failure');
        $this->assertTrue($this->result->isFailure());
    }

    public function testWarningAliasForWarnStatusIsAccepted()
    {
        $this->result->setStatus('warning');
        $this->assertTrue($this->result->isWarning());
    }

    public function testCanSetSuccessStatus()
    {
        $this->result->setStatus(Result::SUCCESS);
        $this->assertTrue($this->result->isSuccess());
    }

    public function testCanSetFailStatus()
    {
        $this->result->setStatus(Result::FAIL);
        $this->assertTrue($this->result->isFailure());
    }

    public function testCanSetWarnStatus()
    {
        $this->result->setStatus(Result::WARN);
        $this->assertTrue($this->result->isWarning());
    }

    public function testCanGetStatusDirectly()
    {
        $this->result->setStatus(Result::SUCCESS);
        $this->assertEquals(Result::SUCCESS, $this->result->getStatus());
    }

    public function testCanSetOutputUsingAnArray()
    {
        $this->result->setOutput(array('LINE_ONE', 'LINE_TWO'));
        $this->assertEquals('LINE_ONE' . PHP_EOL . 'LINE_TWO' . PHP_EOL, $this->result->getOutput());
    }

    public function testCanSetOutputUsingString()
    {
        $this->result->setOutput('TEST_STRING_OUTPUT');
        $this->assertEquals('TEST_STRING_OUTPUT', $this->result->getOutput());
    }

    public function testCanSetAndGetMessage()
    {
        $this->result->setMessage('TEST_MESSAGE');
        $this->assertEquals('TEST_MESSAGE', $this->result->getMessage());
    }

    public function testADefaultMessageIsSet()
    {
        $message = $this->result->getMessage();
        $this->assertTrue(is_string($message));
        $this->assertGreaterThan(0, strlen($message));
    }

    public function testIsFailedByDefault()
    {
        $this->assertTrue($this->result->isFailure());
    }

    public function testCanSetMultipleOptionsAtOnceViaSetOptionsMethod()
    {
        $this->assertTrue($this->result->isFailure());
        $this->result->setOptions(array('message' => 'SET_OPTIONS', 'status' => Result::SUCCESS));
        $this->assertEquals('SET_OPTIONS', $this->result->getMessage());
        $this->assertTrue($this->result->isSuccess());
    }

    public function testCanCallSetOptionsViaConstructorParameter()
    {
        $result = new Result(array('message' => 'SET_OPTIONS', 'status' => Result::SUCCESS));
        $this->assertEquals('SET_OPTIONS', $result->getMessage());
        $this->assertTrue($result->isSuccess());
    }

    /**
     * @expectedException \Gasp\Exception
     */
    public function testAttemptingToSetInvalidOptionViaSetOptionsThrowsException()
    {
        $this->result->setOptions(array('fafafafa' => 'FAFAFAFA'));
    }

    public function testDisplayIncludesBothSummaryAndOutput()
    {
        $display = $this->result->display();
        $this->assertTrue(is_string($display));
        $this->assertContains($this->result->getSummary(), $display);
        $this->assertContains($this->result->getMessage(), $display);
    }

    public function testGetSummaryReturnsString()
    {
        $this->assertTrue(is_string($this->result->getSummary()));
    }
}
