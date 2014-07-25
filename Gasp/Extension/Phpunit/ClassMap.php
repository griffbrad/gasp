<?php

namespace Gasp\Extension\Phpunit;

use Gasp\ClassMap as CoreClassMap;

/**
 * Manage the extension-wide options for the Phpunit Gasp extension and define
 * the available tasks.
 */
class ClassMap extends CoreClassMap
{
    /**
     * The tasks available in the Phpunit extension.
     *
     * @var array
     */
    protected $classes = array(
        'runtests' => '\Gasp\Extension\Phpunit\Task\RunTests',
        'coverage' => '\Gasp\Extension\Phpunit\Task\Coverage'
    );

    /**
     * The phpunit executable that should be used to run the tests.
     *
     * @var string
     */
    private $phpunit;

    /**
     * The path where tests can be found.
     *
     * @var string
     */
    private $path;

    /**
     * The path to your phpunit.xml configuration.  If not specified, we'll
     * attempt to find it in your tests path.
     *
     * @var string
     */
    private $configuration;

    /**
     * The threshold you want to require for the code coverage of your tests.
     *
     * @var float
     */
    private $coverageThreshold;

    /**
     * Set the location of the phpunit executable.
     *
     * @param string $phpunit
     * @return $this
     */
    public function setPhpunit($phpunit)
    {
        $this->phpunit = $phpunit;

        return $this;
    }

    /**
     * Get the phpunit executable associated with this class map.
     *
     * @return string
     */
    public function getPhpunit()
    {
        return $this->phpunit;
    }

    /**
     * Set the path where tests can be found.
     *
     * @param string $path
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = rtrim($path, '/');

        return $this;
    }

    /**
     * Get all the paths assigned to this task.
     *
     * @return array
     */
    public function getPath()
    {
        return $this->path;
    }

    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;

        return $this;
    }

    public function getConfiguration()
    {
        if (null === $this->configuration) {
            $default = $this->path . '/phpunit.xml';

            if (file_exists($default)) {
                $this->configuration = $default;
            }
        }

        return $this->configuration;
    }

    /**
     * Set the code coverage threshold.  If coverage is below this amount, a
     * warning will be generated.
     *
     * @param string $coverageThreshold
     * @return $this
     */
    public function setCoverageThreshold($coverageThreshold)
    {
        if ($coverageThreshold && 1 < $coverageThreshold) {
            $coverageThreshold = $coverageThreshold / 100;
        }

        $this->coverageThreshold = $coverageThreshold;

        return $this;
    }

    /**
     * Get the threshold you require your test suite to hit.
     *
     * @return float
     */
    public function getCoverageThreshold()
    {
        return $this->coverageThreshold;
    }
}
