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
 * The watch task allows you to monitor one or more folders for changes and then
 * trigger other tasks in response.  The watch task uses inotify to monitor
 * folders for changes, so you'll need the inotify PECL extension installed to
 * use it.  Most major distros include that extension in their repos (e.g. on
 * CentOS it is avilable as php-pecl-inotify.
 *
 * You can optionally clear the terminal of all output when displaying tasks by
 * calling setClear().
 *
 * IMPORTANT NOTE FOR VAGRANT USERS: Both the default shared folder implementation
 * and NFS do not work with inotify because they both bypass the Linux kernel VFS,
 * which is where inotify watches for changes.  You can use the rsync shared folder
 * implementation added in 1.5 instead.  On Mac, if you're using rsync, I'd also
 * recommend using this plugin, which makes subsequent Vagrant syncs much quicker
 * with rsync:
 *
 * https://github.com/smerrill/vagrant-gatling-rsync
 */
class Watch extends TaskAbstract
{
    private $tasks = array();

    private $clear = false;

    private $pathsByWatch = array();

    public function addTask($task, $paths)
    {
        if (!is_array($paths)) {
            $paths = array($paths);
        }

        foreach ($paths as $index => $path) {
            $paths[$index] = realpath($path);
        }

        $this->tasks[$task] = $paths;

        return $this;
    }

    public function setClear($clear)
    {
        $this->clear = $clear;

        return $this;
    }

    /**
     * @return Result
     */
    public function run()
    {
        /** @noinspection PhpUndefinedFunctionInspection */
        $inotify = inotify_init();
        $watches = $this->createInotifyWatches($inotify);

        stream_set_blocking($inotify, 0);

        while (true) {
            /** @noinspection PhpUndefinedFunctionInspection */
            $events = inotify_read($inotify);

            if ($events) {
                if ($this->clear) {
                    passthru('bash -c clear');
                }

                $this->runTasksForInotifyEvents($events);
            }

            sleep(1);
        }

        $this->shutdownInotify($inotify, $watches);

        return $this->gasp->result()
            ->setStatus(Result::SUCCESS)
            ->setMessage('And now his watch is ended.');
    }

    private function runTasksForInotifyEvents(array $events)
    {
        $watchesRun = array();

        foreach ($events as $event) {
            // Only run tasks once per each inotify watch in a set of events
            if (!in_array($event['wd'], $watchesRun)) {
                foreach ($this->findTasksForWatch($event['wd']) as $taskName) {
                    $date = date('Y-m-d G:i:s');

                    echo "Detected change ({$date}).  Running {$taskName}..." . PHP_EOL;
                    echo $this->gasp->runTaskByName($taskName)->display();
                    echo PHP_EOL;

                    $watchesRun[] = $event['wd'];
                }
            }
        }

        return $this;
    }

    /**
     * Ensure the inotify PECL extension is installed and that the user has added
     * at least one task prior to running.
     *
     * @throws \Gasp\Exception
     */
    public function validate()
    {
        if (!extension_loaded('inotify')) {
            throw new Exception('inotify extension is needed for watch.');
        }

        if (!count($this->tasks)) {
            throw new Exception('You must add at least one task before running watch.');
        }
    }

    public function createInotifyWatches($inotify)
    {
        $paths   = array();
        $watches = array();

        foreach ($this->tasks as $taskPaths) {
            foreach ($taskPaths as $path) {
                $path = realpath($path);

                if (!in_array($path, $paths)) {
                    $paths[] = $path;
                }
            }
        }

        foreach ($paths as $path) {
            /** @noinspection PhpUndefinedFunctionInspection */
            /** @noinspection PhpUndefinedConstantInspection */
            $watch = inotify_add_watch(
                $inotify,
                $path,
                IN_CREATE | IN_MODIFY | IN_CLOSE_WRITE | IN_ATTRIB
            );

            if (!isset($this->pathsByWatch[$watch])) {
                $this->pathsByWatch[$watch] = array();
            }

            $this->pathsByWatch[$watch][] = $path;

            $watches[] = $watch;
        }

        return $watches;
    }

    private function shutdownInotify($inotify, array $watches)
    {
        foreach ($watches as $watch) {
            /** @noinspection PhpUndefinedFunctionInspection */
            inotify_rm_watch($inotify, $watch);
        }

        fclose($inotify);
    }

    /**
     * Find the tasks that should be run when the supplied inotify watch ID has
     * triggered an event.
     *
     * @param integer $watchId
     * @return array
     */
    private function findTasksForWatch($watchId)
    {
        if (!isset($this->pathsByWatch[$watchId])) {
            return array();
        }

        $paths = $this->pathsByWatch[$watchId];
        $tasks = array();

        foreach ($paths as $path) {
            foreach ($this->tasks as $taskName => $taskPaths) {
                if (in_array($path, $taskPaths)) {
                    $tasks[] = $taskName;
                }
            }
        }

        return $tasks;
    }
}
