<?php

namespace Oro\Bundle\ShippingBundle\EventListener;

/**
 * Defines the contract for events that provide access to entity data and submitted form data.
 *
 * This interface is used by event listeners that need to access both the entity being processed
 * and the data submitted through forms, allowing for validation and data manipulation
 * during entity lifecycle events.
 */
interface EntityDataAwareEventInterface
{
    /**
     * @return array|null
     */
    public function getSubmittedData();

    /**
     * @return \ArrayObject
     */
    public function getData();

    /**
     * @return object
     */
    public function getEntity();
}
