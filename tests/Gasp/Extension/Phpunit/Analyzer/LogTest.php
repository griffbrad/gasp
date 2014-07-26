<?php

namespace Gasp\Extension\Phpunit\Analyzer;

use Gasp\Terminal;

class LogTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Log
     */
    private $analyzer;

    public function setUp()
    {
        $this->analyzer = new Log();
    }

    public function testCmdArgWillContainEscapedFilename()
    {
        $this->analyzer->setFile('fafafafa');
        $this->assertContains(escapeshellarg('fafafafa'), $this->analyzer->getCmdArg());
    }

    public function testCanDeleteFile()
    {
        $file = tempnam('/tmp', 'gasp-delete-file-test');
        $this->analyzer->setFile($file);
        $this->analyzer->deleteFile();
        $this->assertFalse(file_exists($file));
    }

    /**
     * @expectedException \Gasp\Exception
     */
    public function testExceptionIsThrownWhenInvalidFileIsUsed()
    {
        $this->analyzer->setFile(__DIR__ . '/log-invalid/invalid.json');
        $this->analyzer->analyze();
    }

    public function testFileWithFailureIsAnalyzedCorrectly()
    {
        $this->analyzer->setFile(__DIR__ . '/log-with-failure/fail-log.json');
        $this->analyzer->analyze();

        $this->assertEquals(1, $this->analyzer->getFailureCount());
        $this->assertEquals(0, $this->analyzer->getSkippedCount());
        $this->assertEquals(1, $this->analyzer->getTestCount());
        $this->assertTrue($this->analyzer->isFailure());
        $this->assertFalse($this->analyzer->isWarning());
        $this->assertFalse($this->analyzer->isSuccess());
        $this->assertContains('Fail', $this->analyzer->getMessage());
    }

    public function testFileWithSkipFailureAndPassingTestIsAnalyzedCorrectly()
    {
        $this->analyzer->setFile(__DIR__ . '/log-mixed/mixed-log.json');
        $this->analyzer->analyze();

        $this->assertEquals(1, $this->analyzer->getFailureCount());
        $this->assertEquals(1, $this->analyzer->getSkippedCount());
        $this->assertEquals(3, $this->analyzer->getTestCount());
        $this->assertTrue($this->analyzer->isFailure());
        $this->assertContains('Fail', $this->analyzer->getMessage());
    }

    public function testBothSkippedAndFailedTestsAreIncludedInOutput()
    {
        $this->analyzer->setFile(__DIR__ . '/log-mixed/mixed-log.json');
        $this->analyzer->analyze();

        $output = $this->analyzer->getOutput(new Terminal());

        $this->assertContains('SKIPPED_TEST_MESSAGE', $output);
        $this->assertContains('testFalseIsTrue', $output);
    }

    public function testFailureAndSkipCountsOfZeroResultInSuccess()
    {
        $this->assertTrue($this->analyzer->isSuccess());
        $this->assertContains('Success', $this->analyzer->getMessage());
    }
}
