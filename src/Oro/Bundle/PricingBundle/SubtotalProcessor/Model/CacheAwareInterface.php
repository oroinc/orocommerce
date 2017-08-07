<?php

namespace Oro\Bundle\PricingBundle\SubtotalProcessor\Model;

/**
 * Interface CacheAwareInterface is implemented by subtotal providers which are able to store calculated
 * subtotal for entity in some storage.
 */
interface CacheAwareInterface
{
    /**
     * @param object $entity
     * @return Subtotal|Subtotal[]
     */
    public function getCachedSubtotal($entity);

    /**
     * Checks that provider supports entity
     *
     * @param object $entity
     * @return bool
     */
    public function supportsCachedSubtotal($entity);
}
