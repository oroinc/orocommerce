<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched before indexation to collect additional context data for the website search indexing process.
 *
 * Event listeners can add custom context values that will be available throughout the indexation lifecycle.
 * This context is used to pass information such as website IDs, localization IDs, and other parameters
 * that influence how entities are indexed and how search queries are executed. The collected context
 * is accessible in subsequent indexation events and can be used to customize indexing behavior.
 */
class CollectContextEvent extends Event
{
    public const NAME = 'oro_website_search.event.collect_context';

    /**
     * @var array
     */
    private $context = [];

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
