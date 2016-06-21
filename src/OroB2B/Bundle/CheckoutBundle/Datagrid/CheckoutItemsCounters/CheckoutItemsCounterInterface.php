<?php

namespace OroB2B\Bundle\CheckoutBundle\Datagrid\CheckoutItemsCounters;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Interface for line item counters. Checkout may have different source so it is required
 * to provide a counter for each of them.
 */
interface CheckoutItemsCounterInterface
{
    /**
     * Counts items related to a concrete source
     *
     * @param EntityManagerInterface $em
     * @param array $ids
     * @return array - key is the ID, value is the COUNT
     */
    public function countItems(EntityManagerInterface $em, array $ids);
}
