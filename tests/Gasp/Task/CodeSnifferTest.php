<?php

namespace Gasp\Task;

use Gasp\Run;

class CodeSnifferTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CodeSniffer
     */
    private $sniff;

    public function setUp()
    {
        $gasp = new Run();

        $this->sniff = new CodeSniffer();
        $this->sniff->setGasp($gasp);
    }

    /**
     * @expectedException \Gasp\Exception
     */
    public function testValidatingWithNoPhpcsExecutableSetThrowsException()
    {
        $this->sniff->validate();
    }

    /**
     * @expectedException \Gasp\Exception
     */
    public function testAttemptingToUseNonExistentPhpcsExecutableThrowsException()
    {
        $this->sniff
            ->setPhpcs('fafafafa')
            ->validate();
    }

    /**
     * @expectedException \Gasp\Exception
     */
    public function testAttemptingToUseNonExecutablePhpcsExecutableThrowsException()
    {
        $this->sniff
            ->setPhpcs(__FILE__)
            ->validate();
    }

    /**
     * @expectedException \Gasp\Exception
     */
    public function testValidatingWithoutAnyPathsDefinedThrowsException()
    {
        $this->sniff
            ->setPhpcs('./vendor/bin/phpcs')
            ->validate();
    }

    public function testEmptyFailedPhpcsResultsInFailure()
    {
        $gasp   = new Run();
        $sniff  = $this->getMock('\Gasp\Task\CodeSniffer', array('execPhpcs'), array());
        $result = $gasp->result();

        $result
            ->setStatus('fail')
            ->setOutput('');

        $sniff->expects($this->once())
            ->method('execPhpcs')
            ->will($this->returnValue($result));

        /* @var $sniff \Gasp\Task\CodeSniffer */
        $sniff
            ->setGasp($gasp)
            ->setPhpcs('./vendor/bin/phpcs')
            ->setPaths([__DIR__ . '/sniff-test-files/all-valid']);

        $this->assertTrue($sniff->run()->isFailure());
    }

    public function testInvalidJsonInPhpcsResultsCausesFailure()
    {
        $gasp   = new Run();
        $sniff  = $this->getMock('\Gasp\Task\CodeSniffer', array('execPhpcs'), array());
        $result = $gasp->result();

        $result
            ->setStatus('fail')
            ->setOutput('{{{{{{{{');

        $sniff->expects($this->once())
            ->method('execPhpcs')
            ->will($this->returnValue($result));

        /* @var $sniff \Gasp\Task\CodeSniffer */
        $sniff
            ->setGasp($gasp)
            ->setPhpcs('./vendor/bin/phpcs')
            ->setPaths([__DIR__ . '/sniff-test-files/all-valid']);

        $this->assertTrue($sniff->run()->isFailure());
    }

    public function testRunningWithAllValidFilesGivesSuccessfulResultAndNoOutputOtherThanSummary()
    {
        $this->sniff
            ->setPhpcs('./vendor/bin/phpcs')
            ->addPath(__DIR__ . '/sniff-test-files/all-valid');


        $this->sniff->validate();

        $result = $this->sniff->run();

        $this->assertTrue($result->isSuccess());
        $this->assertEmpty($result->getOutput());
        $this->assertEquals($result->getSummary(), $result->display());
    }

    public function testRunningWithInvalidFilesGivesFailedResultAndOutputExplainingErrors()
    {
        $result = $this->sniff
            ->setPhpcs('./vendor/bin/phpcs')
            ->addPath(__DIR__ . '/sniff-test-files/some-invalid')
            ->run();

        $this->assertTrue($result->isFailure());
        $this->assertContains('ERROR', $result->getOutput());
        $this->assertContains('WARNING', $result->getOutput());
        $this->assertStringStartsWith($result->getSummary(), $result->display());
    }

    public function testRunningWithOnlyWarningsAndNoErrorsWillShowWarningCountInResults()
    {
        $result = $this->sniff
            ->setPhpcs('./vendor/bin/phpcs')
            ->addPath(__DIR__ . '/sniff-test-files/just-warnings')
            ->run();

        $this->assertTrue($result->isWarning());
        $this->assertContains('WARNING', $result->getOutput());
        $this->assertContains('warnings', $result->display());
    }

    public function testCanSetMultiplePathsViaSetPathsMethod()
    {
        $this->sniff->setPaths([__DIR__, '/etc/']);
        $this->assertEquals([__DIR__, '/etc/'], $this->sniff->getPaths());
    }

    public function testPhpExtensionIsUsedByDefault()
    {
        $this->assertContains('php', $this->sniff->getExtensions());
    }

    public function testAddExtensionNormalizesByRemovingLeadingDot()
    {
        $this->sniff->setExtensions([]);
        $this->sniff->addExtension('.dot');
        $this->assertEquals(['dot'], $this->sniff->getExtensions());
    }

    public function testSetExtensionsNormalizesByRemovingLeadingDot()
    {
        $this->sniff->setExtensions(['.dot', '.period']);
        $this->assertEquals(['dot', 'period'], $this->sniff->getExtensions());
    }
}
