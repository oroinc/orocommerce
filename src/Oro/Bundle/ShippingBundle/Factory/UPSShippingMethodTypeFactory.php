<?php

namespace Oro\Bundle\ShippingBundle\Factory;

use Oro\Bundle\ShippingBundle\Method\UPS\UPSShippingMethodType;

class UPSShippingMethodTypeFactory
{
    /**
     * @param string|int $identifier
     * @param string $label
     * @return UPSShippingMethodType
     */
    public function create($identifier, $label)
    {
        $entity = new UPSShippingMethodType();
        $entity->setIdentifier($identifier);
        $entity->setLabel($label);

        return $entity;
    }
}
