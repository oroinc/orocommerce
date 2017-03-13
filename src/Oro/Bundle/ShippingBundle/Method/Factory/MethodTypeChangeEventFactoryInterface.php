<?php

namespace Oro\Bundle\ShippingBundle\Method\Factory;

use Oro\Bundle\ShippingBundle\Method\Event\MethodTypeChangeEvent;

interface MethodTypeChangeEventFactoryInterface
{
    /**
     * @param array  $availableTypes
     * @param string $methodIdentifier
     *
     * @return MethodTypeChangeEvent
     */
    public function create(array $availableTypes, $methodIdentifier);
}
