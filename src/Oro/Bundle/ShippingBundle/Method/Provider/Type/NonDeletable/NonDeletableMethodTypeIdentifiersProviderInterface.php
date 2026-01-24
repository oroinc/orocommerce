<?php

namespace Oro\Bundle\ShippingBundle\Method\Provider\Type\NonDeletable;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;

/**
 * Defines the contract for providers that identify non-deletable shipping method types.
 *
 * Implementations of this interface determine which shipping method types cannot be deleted
 * because they are actively used in shipping rules or other configurations,
 * preventing accidental removal of required shipping options.
 */
interface NonDeletableMethodTypeIdentifiersProviderInterface
{
    /**
     * @param ShippingMethodInterface $shippingMethod
     *
     * @return string[]
     */
    public function getMethodTypeIdentifiers(ShippingMethodInterface $shippingMethod);
}
