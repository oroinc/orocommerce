<?php

namespace Oro\Bundle\ShippingBundle\Method\Provider\Type\NonDeletable;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;

interface NonDeletableMethodTypeIdentifiersProviderInterface
{
    /**
     * @param ShippingMethodInterface $shippingMethod
     *
     * @return string[]
     */
    public function getMethodTypeIdentifiers(ShippingMethodInterface $shippingMethod);
}
