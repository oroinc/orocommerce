<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class CollectContextEvent extends Event
{
    /**
     * @var array
     */
    private $context = [];

    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function addContextValue($name, $value)
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException('Context value name must be a string');
        }

        $this->context[$name] = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }
}
