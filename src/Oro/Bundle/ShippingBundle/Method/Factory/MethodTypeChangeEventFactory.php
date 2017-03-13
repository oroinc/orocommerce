<?php

namespace Oro\Bundle\ShippingBundle\Method\Factory;

use Oro\Bundle\ShippingBundle\Method\Event\MethodTypeChangeEvent;

class MethodTypeChangeEventFactory implements MethodTypeChangeEventFactoryInterface
{
    /**
     * @param array  $availableTypes
     * @param string $methodIdentifier
     *
     * @return MethodTypeChangeEvent
     */
    public function create(array $availableTypes, $methodIdentifier)
    {
        return new MethodTypeChangeEvent($availableTypes, $methodIdentifier);
    }
}
