<?php

namespace Oro\Bundle\PricingBundle\SubtotalProcessor\Model;

/**
 * Represents a service that is used to get subtotals.
 */
interface SubtotalProviderInterface
{
    /**
     * Gets entity subtotal.
     *
     * @param object $entity
     *
     * @return Subtotal[]|Subtotal
     */
    public function getSubtotal($entity);

    /**
     * Checks if this provider supports the given entity.
     *
     * @param object $entity
     *
     * @return bool
     */
    public function isSupported($entity);
}
