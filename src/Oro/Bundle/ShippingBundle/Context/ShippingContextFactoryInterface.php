<?php

namespace Oro\Bundle\ShippingBundle\Context;

interface ShippingContextFactoryInterface
{
    /**
     * @param object $entity
     *
     * @return ShippingContextInterface
     */
    public function create($entity);
}
