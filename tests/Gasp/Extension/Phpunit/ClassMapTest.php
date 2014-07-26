<?php

namespace Gasp\Extension\Phpunit;

class ClassMapTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ClassMap
     */
    private $classMap;

    public function setUp()
    {
        $this->classMap = new ClassMap();
    }

    public function testCanManuallySpecifyConfigurationLocation()
    {
        $this->classMap->setConfiguration('fafafafa');
        $this->assertEquals('fafafafa', $this->classMap->getConfiguration());
    }

    public function testAutoDetectConfigurationInPath()
    {
        $this->classMap->setPath(__DIR__ . '/path-with-phpunitxml');
        $this->assertNotEmpty($this->classMap->getConfiguration());
    }

    public function testCanHaveNoConfigurationIfNotSetOrAutoDetected()
    {
        $this->assertEmpty($this->classMap->getConfiguration());
    }

    public function testCanSetAndGetPath()
    {
        $this->classMap->setPath(__DIR__);
        $this->assertEquals(__DIR__, $this->classMap->getPath());
    }

    public function testCanSetAndGetPhpunitExecutable()
    {
        $this->classMap->setPhpunit('fafafafa');
        $this->assertEquals('fafafafa', $this->classMap->getPhpunit());
    }

    public function testWholeNumbersPassedToSetCoverageThresholdAreConvertedToPercentageAmount()
    {
        $this->classMap->setCoverageThreshold(99);
        $this->assertEquals(.99, $this->classMap->getCoverageThreshold());
    }

    public function testPercentageAmountsPassedToSetCoverageThresholdAreNotMessedWith()
    {
        $this->classMap->setCoverageThreshold(.75);
        $this->assertEquals(.75, $this->classMap->getCoverageThreshold());
    }
}
