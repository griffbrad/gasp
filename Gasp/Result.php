<?php

namespace Gasp;

use Gasp\Result\ResultInterface;

class Result implements ResultInterface
{
    const SUCCESS = 'success';

    const WARN = 'warn';

    const FAIL = 'fail';

    private $message = '<no message available>';

    private $status = self::FAIL;

    private $output;

    public function __construct(array $options = array())
    {
        $this->setOptions($options);
    }

    public function display()
    {
        $output = $this->getSummary();

        if ($this->isSuccess()) {
            return $output;
        }

        $output .= $this->getOutput();

        return $output;
    }

    public function setOptions(array $options)
    {
        foreach ($options as $name => $value) {
            $setter = 'set' . ucfirst($name);

            if (!method_exists($this, $setter)) {
                throw new Exception("Option '{$name}' does not exist.");
            } else {
                $this->$setter($value);
            }
        }

        return $this;
    }

    public function getSummary()
    {
        return (string) new Summary($this);
    }

    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setOutput($output)
    {
        if (is_array($output)) {
            $output = implode(PHP_EOL, $output) . PHP_EOL;
        }

        $this->output = $output;

        return $this;
    }

    public function getOutput()
    {
        return $this->output;
    }

    public function setStatus($status)
    {
        $status = strtolower($status);

        if ('failure' === $status) {
            $status = 'fail';
        } elseif ('warning' === $status) {
            $status = 'warn';
        }

        if (!$this->isValidStatus($status)) {
            throw new Exception("{$status} is not a valid status.");
        }

        $this->status = $status;

        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function isFailure()
    {
        return self::FAIL === $this->status;
    }

    public function isWarning()
    {
        return self::WARN === $this->status;
    }

    public function isSuccess()
    {
        return self::SUCCESS === $this->status;
    }

    private function isValidStatus($status)
    {
        switch ($status) {
            case self::SUCCESS :
            case self::WARN :
            case self::FAIL :
                return true;
            default:
                return false;
        }
    }
}
