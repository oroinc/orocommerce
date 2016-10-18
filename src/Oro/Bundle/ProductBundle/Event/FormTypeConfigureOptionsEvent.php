<?php

namespace Oro\Bundle\ProductBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class FormTypeConfigureOptionsEvent extends Event
{
    /**
     * @var array
     */
    protected $options;

    public function __construct()
    {
        $this->options = [];
    }

    /**
     * @return mixed
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param string $optionName
     * @param mixed $value
     * @return $this
     */
    public function setOption($optionName, $value)
    {
        $this->options[$optionName] = $value;

        return $this;
    }

    /**
     * @param string $optionName
     * @return mixed
     */
    public function getOption($optionName)
    {
        if (!$this->hasOption($optionName)) {
            return null;
        }

        return $this->options[$optionName];
    }

    /**
     * @param string $optionName
     * @return bool
     */
    public function hasOption($optionName)
    {
        return array_key_exists($optionName, $this->options);
    }
}
