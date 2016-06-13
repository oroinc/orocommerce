<?php

namespace OroB2B\Bundle\CheckoutBundle\Datagrid\CheckoutSource;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Interface for classes that will provide a source entity and a link to view it, for checkout data grid.
 */
interface CheckoutSourceDefinerInterface
{
    /**
     * Look for sources and return array of CheckoutSourceDefinition DTO classes
     *
     * @param EntityManagerInterface $em
     * @param array $ids
     * @return CheckoutSourceDefinition[]
     */
    public function loadSources(EntityManagerInterface $em, array $ids);
}
