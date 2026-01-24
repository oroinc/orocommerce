<?php

namespace Oro\Bundle\ShippingBundle\Method\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when a shipping method is removed from the system.
 *
 * This event notifies listeners that a shipping method has been removed, allowing them to clean up
 * related configurations, rules, and data that reference the removed method.
 */
class MethodRemovalEvent extends Event
{
    const NAME = 'oro_shipping.method_removal';

    /**
     * @var int|string
     */
    private $methodId;

    /**
     * @param int|string $id
     */
    public function __construct($id)
    {
        $this->methodId = $id;
    }

    /**
     * @return int|string
     */
    public function getMethodIdentifier()
    {
        return $this->methodId;
    }
}
