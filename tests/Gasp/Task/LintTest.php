<?php

/**
 * Gasp
 *
 * @link https://github.com/griffbrad/gasp
 */

namespace Gasp\Task;

use Gasp\Run;

class LintTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Lint
     */
    private $lint;

    public function setUp()
    {
        $gasp = new Run();

        $this->lint = new Lint();
        $this->lint->setGasp($gasp);
    }

    /**
     * @expectedException \Gasp\Exception
     */
    public function testValidatingWithNoPhpBinarySetThrowsException()
    {
        $this->lint->validate();
    }

    /**
     * @expectedException \Gasp\Exception
     */
    public function testAttemptingToUseNonExistentPhpBinaryThrowsException()
    {
        $this->lint
            ->setPhp('fafafafa')
            ->validate();
    }

    /**
     * @expectedException \Gasp\Exception
     */
    public function testAttemptingToUseNonExecutablePhpBinaryThrowsException()
    {
        $this->lint
            ->setPhp(__FILE__)
            ->validate();
    }

    /**
     * @expectedException \Gasp\Exception
     */
    public function testValidatingWithoutAnyPathsDefinedThrowsException()
    {
        $this->lint
            ->setPhp('/usr/bin/php')
            ->validate();
    }

    public function testRunningWithAllValidFilesGivesSuccessfulResultAndNoOutputOtherThanSummary()
    {
        $this->lint
            ->setPhp('/usr/bin/php')
            ->addPath(__DIR__ . '/lint-test-files/all-valid');


        $this->lint->validate();

        $result = $this->lint->run();

        $this->assertTrue($result->isSuccess());
        $this->assertEmpty($result->getOutput());
        $this->assertEquals($result->getSummary(), $result->display());
    }

    public function testRunningWithInvalidFilesGivesFailedResultAndOutputExplainingErrors()
    {
        $result = $this->lint
            ->setPhp('/usr/bin/php')
            ->addPath(__DIR__ . '/lint-test-files/some-invalid')
            ->run();

        $this->assertTrue($result->isFailure());
        $this->assertContains('Error', $result->getOutput());
        $this->assertStringStartsWith($result->getSummary(), $result->display());
    }

    public function testCanSetMultiplePathsViaSetPathsMethod()
    {
        $this->lint->setPaths([__DIR__, '/etc/']);
        $this->assertEquals([__DIR__, '/etc/'], $this->lint->getPaths());
    }

    public function testPhpExtensionIsUsedByDefault()
    {
        $this->assertContains('php', $this->lint->getExtensions());
    }

    public function testAddExtensionNormalizesByRemovingLeadingDot()
    {
        $this->lint->setExtensions([]);
        $this->lint->addExtension('.dot');
        $this->assertEquals(['dot'], $this->lint->getExtensions());
    }

    public function testSetExtensionsNormalizesByRemovingLeadingDot()
    {
        $this->lint->setExtensions(['.dot', '.period']);
        $this->assertEquals(['dot', 'period'], $this->lint->getExtensions());
    }
}
