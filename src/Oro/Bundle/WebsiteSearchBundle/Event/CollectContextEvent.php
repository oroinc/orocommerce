<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class CollectContextEvent extends Event
{
    const NAME = 'oro_website_search.event.collect_context';

    /**
     * @var array
     */
    private $context = [];

    /**
     *
     * @param array $context
     */
    public function __construct(array $context)
    {
        $this->context = $context;
    }

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

        if (empty($name)) {
            throw new \InvalidArgumentException('Context value name cannot be empty');
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
