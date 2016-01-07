Gasp
====

A PHP task runner with an API similar to Gulp's.

Gasp makes it easy to create build scripts you can share with other devs on
your team.  You configure Gasp with normal PHP code, rather than being stuck
with an XML or JSON syntax that manages to be both complex and limiting.
You can easily compose multiple Gasp tasks into a single command or add your
own with a simple callback.


Installation
------------

You'll first install Gasp via composer.  Add it to your composer.json's
require-dev section:

    {
        "require-dev": {
            "griffbrad/gasp": "1.*"
        }
    }

You'll then be able to run Gasp from your vendor directory:

    ./vendor/bin/gasp


Getting Started
---------------

You'll need a gaspfile in your project folder.  If you installed Gasp via
Composer, you'd place your gaspfile in that same location as your vendor
folder.  Here's a simple gaspfile example:

    <?php

    $gasp->sniff()
        ->setPhpcs('./vendor/bin/phpcs')
        ->setStandard('PSR2')
        ->addPath('admin')
        ->addPath('library/Monitoring');

    $gasp->lint()
        ->setPhp('/usr/bin/php')
        ->addPath('admin')
        ->addPath('library/Monitoring');

    $gasp->task('qa', ['sniff', 'lint']);

Notice the custom "qa" task that composes the built-in "sniff" and "lint"
tasks.  You can also define custom tasks using a closure, rather than an
array of task names.

To call any of the tasks defined in your gulpfile, just pass the task
name as the first argument to gasp:

    ./vendor/bin/gasp qa

We plan to add many more tasks over time, but you can use custom tasks
or the built-in exec tasks to fill in the gaps in the meantime.


Defining custom tasks with callbacks
------------------------------------

When implementing a custom task using a callback, you need to return a
result object so Gasp knows how things went:

    <?php

    $gasp->task('custom', function () use ($gasp) {
        $itWorked = call_your_custom_functions_to_do_important_work();
        $result   = $gasp->result();

        if ($itWorked) {
            $result
                ->setStatus('success')
                ->setMessage('Hey!  It worked!');
        } else {
            $result
                ->setStatus('failure')
                ->setMessage('Hm.  Try again.');
        }

        return $result;
    });


Using exec to run other programs
--------------------------------

The built-in exec task lets you run other programs and will automatically generate
a result based upon the exit status of the program:

    <?php

    $gasp->task('date', function () use ($gasp) {
        return $gasp->exec('date');
    });


