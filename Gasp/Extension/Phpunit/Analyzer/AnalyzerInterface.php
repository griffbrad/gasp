<?php

namespace Gasp\Extension\Phpunit\Analyzer;

use Gasp\Run;

interface AnalyzerInterface
{
    /**
     * @param Run $gasp
     * @return AnalyzerInterface
     */
    public function setGasp(Run $gasp);

    public function setFile($file);

    public function deleteFile();

    public function getCmdArg();

    public function analyze();

    public function getOutput();

    public function getMessage();

    public function isWarning();

    public function isSuccess();

    public function isFailure();
}
