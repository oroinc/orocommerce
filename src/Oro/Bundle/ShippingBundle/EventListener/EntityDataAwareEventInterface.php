<?php

namespace Oro\Bundle\ShippingBundle\EventListener;

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
