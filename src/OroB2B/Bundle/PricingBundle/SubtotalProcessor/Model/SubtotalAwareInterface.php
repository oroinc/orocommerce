<?php
namespace OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model;

/**
 * Interface for entities with subtotal.
 */
interface SubtotalAwareInterface
{
    /**
     * @return float
     */
    public function getSubtotal();
}
