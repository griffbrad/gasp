<?php

namespace Gasp\Extension\Phpunit\Analyzer;

interface AnalyzerInterface
{
    public function setFile($file);

    public function deleteFile();

    public function getCmdArg();

    public function analyze();

    public function getMessage();

    public function isWarning();

    public function isSuccess();

    public function isFailure();
}
