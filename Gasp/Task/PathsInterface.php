<?php

/**
 * Gasp
 *
 * @link https://github.com/griffbrad/gasp
 */

namespace Gasp\Task;

/**
 * This interface helps to ensure that tasks dealing with paths have a
 * consistent API.
 */
interface PathsInterface
{
    /**
     * Add a single path to the task.
     *
     * @param string $path
     * @return PathsInterface
     */
    public function addPath($path);

    /**
     * Override all existing paths on the task with the provided array.
     *
     * @param array $paths
     * @return PathsInterface
     */
    public function setPaths(array $paths);

    /**
     * Get all the paths assigned to this task.
     *
     * @return array
     */
    public function getPaths();
}
