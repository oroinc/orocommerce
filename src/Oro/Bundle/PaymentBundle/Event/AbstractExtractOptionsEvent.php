<?php

namespace Oro\Bundle\PaymentBundle\Event;

use Symfony\Component\EventDispatcher\Event;

abstract class AbstractExtractOptionsEvent extends Event
{
    /** @var array */
    protected $keys;

    /** @var array */
    protected $options = [];

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     * @return array
     * @throws \InvalidArgumentException
     */
    public function applyKeys(array $options)
    {
        if (count($this->keys) !== count($options)) {
            throw new \InvalidArgumentException('Different number of keys and passed options was expected');
        }

        return array_combine($this->keys, $options);
    }
}
