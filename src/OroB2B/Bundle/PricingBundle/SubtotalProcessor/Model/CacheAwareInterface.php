<?php

namespace OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model;

/**
 * Interface CacheAwareInterface is implemented by subtotal providers which are able to store calculated
 * subtotal for entity in some storage.
 */
interface CacheAwareInterface
{
    /**
     * @param object $entity
     * @return Subtotal
     */
    public function getCachedSubtotal($entity);
}
