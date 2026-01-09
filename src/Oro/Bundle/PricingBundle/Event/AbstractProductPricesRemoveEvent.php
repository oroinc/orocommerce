<?php

namespace Oro\Bundle\PricingBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Provides common functionality for events related to product price removal.
 *
 * This base class encapsulates arguments that define which product prices should be removed.
 * Subclasses should extend this to create specific price removal events that are dispatched
 * when prices need to be deleted based on various criteria.
 */
abstract class AbstractProductPricesRemoveEvent extends Event
{
    /**
     * @var array
     */
    protected $args = [];

    public function __construct(array $args = [])
    {
        $this->args = $args;
    }

    /**
     * @return array
     */
    public function getArgs()
    {
        return $this->args;
    }
}
