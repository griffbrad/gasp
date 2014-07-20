<?php

/**
 * Gasp
 *
 * @link https://github.com/griffbrad/gasp
 */

namespace Gasp\Task;

use Gasp\Exception;
use Gasp\Result;

/**
 * Run an external command and use its exit status and output to generate the
 * result for the task.
 *
 * This task is used by many other built-in tasks, but it can also be useful in
 * custom tasks you define in your gaspfile:
 *
 * <code>
 * $gasp->task('my-command', function () use ($gasp) {
 *     return $gasp->exec()
 *         ->setCmd('my-custom-command --arg=1')
 *         ->run();
 * });
 * </code>
 *
 * You could then call that custom task like this:
 *
 * <code>
 * ./vendor/bin/gasp my-command
 * </code>
 *
 * If the command generates a non-zero exit status, gasp will note that it failed
 * in the result.
 */
class Exec extends TaskAbstract
{
    /**
     * The command that should be run.
     *
     * @var string
     */
    private $cmd;

    /**
     * Set the command that the exec task will run.
     *
     * @param $cmd
     * @return $this
     */
    public function setCmd($cmd)
    {
        $this->cmd = $cmd;

        return $this;
    }

    /**
     * Run the task.
     *
     * If a non-zero exit status is generated, the Result will be a failure.
     * Whether it fails or succeeds, you will be able to get the output generated
     * by the command.
     *
     * @return Result
     */
    public function run()
    {
        $this->validate();

        exec($this->cmd, $output, $exitStatus);

        $result = $this->gasp->result();

        if (0 !== $exitStatus) {
            $result
                ->setStatus(Result::FAIL)
                ->setMessage("Command failed to execute: {$this->cmd}");
        } else {
            $result
                ->setStatus(Result::SUCCESS)
                ->setMessage("Command completed successfully: {$this->cmd}");
        }

        $result->setOutput($output);

        return $result;
    }

    /**
     * Ensure setCmd() has been called prior to running the task.
     *
     * @throws \Gasp\Exception
     */
    public function validate()
    {
        if (!$this->cmd) {
            throw new Exception('cmd required to run exec task.');
        }
    }
}
