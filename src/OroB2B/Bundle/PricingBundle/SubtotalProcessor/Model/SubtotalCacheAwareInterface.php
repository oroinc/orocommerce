<?php

namespace OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model;

/**
 * Interface is implemented by subtotal providers which operate entities implemented SubtotalAwareInterface.
 */
interface SubtotalCacheAwareInterface
{
    /**
     * @param SubtotalAwareInterface $entity
     * @return Subtotal
     */
    public function getCachedSubtotal(SubtotalAwareInterface $entity);
}
