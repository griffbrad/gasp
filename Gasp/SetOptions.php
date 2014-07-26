<?php

namespace Gasp;

trait SetOptions
{
    /**
     * Set multiple options on this object at once rather than calling
     * individual setter methods for each option.
     *
     * @param array $options
     * @return $this
     * @throws Exception
     */
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
}
