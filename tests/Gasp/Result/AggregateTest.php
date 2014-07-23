<?php

namespace Gasp\Result;

use Gasp\Result;

class AggregateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Aggregate
     */
    private $result;

    public function setUp()
    {
        $this->result = new Aggregate();
    }

    public function testCanAddResultViaAddMethod()
    {
        $this->result->add(new Result());
        $this->result->add(new Result());

        $this->assertEquals(2, count($this->result->getResults()));
    }

    public function testCanAddResultViaAddResultMethod()
    {
        $this->result->addResult(new Result());
        $this->result->addResult(new Result());

        $this->assertEquals(2, count($this->result->getResults()));
    }

    public function testDisplayMethodIncludesAllTheAddedResults()
    {
        $one = new Result();

        $one
            ->setMessage('RESULT_ONE')
            ->setStatus(Result::SUCCESS);

        $two = new Result();

        $two
            ->setMessage('RESULT_TWO')
            ->setStatus(Result::SUCCESS);

        $this->result->addResult($one);
        $this->result->addResult($two);

        $this->assertContains('RESULT_ONE', $this->result->display());
        $this->assertContains('RESULT_TWO', $this->result->display());
    }

    public function testIsSuccessReturnsTrueWhenAllResultsAreSuccessful()
    {
        $one = new Result();

        $one
            ->setMessage('RESULT_ONE')
            ->setStatus(Result::SUCCESS);

        $two = new Result();

        $two
            ->setMessage('RESULT_TWO')
            ->setStatus(Result::SUCCESS);

        $this->result->addResult($one);
        $this->result->addResult($two);

        $this->assertTrue($this->result->isSuccess());
    }

    public function testAnyResultNotSucceededMakesIsSuccessReturnFalse()
    {
        $one = new Result();

        $one
            ->setMessage('RESULT_ONE')
            ->setStatus(Result::SUCCESS);

        $two = new Result();

        $two
            ->setMessage('RESULT_TWO')
            ->setStatus(Result::WARN);

        $this->result->addResult($one);
        $this->result->addResult($two);

        $this->assertFalse($this->result->isSuccess());
    }

    public function testIsWarningReturnsTrueWhenAllResultsAreWarnings()
    {
        $one = new Result();

        $one
            ->setMessage('RESULT_ONE')
            ->setStatus(Result::WARN);

        $two = new Result();

        $two
            ->setMessage('RESULT_TWO')
            ->setStatus(Result::WARN);

        $this->result->addResult($one);
        $this->result->addResult($two);

        $this->assertTrue($this->result->isWarning());
    }

    public function testAnyResultNotWarningsMakesIsWarningReturnFalse()
    {
        $one = new Result();

        $one
            ->setMessage('RESULT_ONE')
            ->setStatus(Result::SUCCESS);

        $two = new Result();

        $two
            ->setMessage('RESULT_TWO')
            ->setStatus(Result::WARN);

        $this->result->addResult($one);
        $this->result->addResult($two);

        $this->assertFalse($this->result->isWarning());
    }

    public function testAnyResultFailingMakesIsFailureReturnTrue()
    {
        $one = new Result();

        $one
            ->setMessage('RESULT_ONE')
            ->setStatus(Result::FAIL);

        $two = new Result();

        $two
            ->setMessage('RESULT_TWO')
            ->setStatus(Result::SUCCESS);

        $this->result->addResult($one);
        $this->result->addResult($two);

        $this->assertTrue($this->result->isFailure());
    }

    public function testAllResultsSucceedingMakesIsFailureReturnFalse()
    {
        $one = new Result();

        $one
            ->setMessage('RESULT_ONE')
            ->setStatus(Result::SUCCESS);

        $two = new Result();

        $two
            ->setMessage('RESULT_TWO')
            ->setStatus(Result::SUCCESS);

        $this->result->addResult($one);
        $this->result->addResult($two);

        $this->assertFalse($this->result->isFailure());
    }
}
