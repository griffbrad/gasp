<?php

namespace Gasp\Result;

interface ResultInterface
{
    public function display();

    public function isSuccess();

    public function isFailure();

    public function isWarning();
}
