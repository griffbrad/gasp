<?php

namespace Gasp\Extension\Phpunit\Analyzer;

use Gasp\Terminal;

interface AnalyzerInterface
{
    public function setFile($file);

    public function deleteFile();

    public function getCmdArg();

    public function analyze();

    public function getOutput(Terminal $terminal);

    public function getMessage();

    public function isWarning();

    public function isSuccess();

    public function isFailure();
}
