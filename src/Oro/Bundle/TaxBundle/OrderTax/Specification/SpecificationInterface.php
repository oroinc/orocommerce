<?php

namespace Oro\Bundle\TaxBundle\OrderTax\Specification;

/**
 * Base interface for the OrderTax specifications
 */
interface SpecificationInterface
{
    /**
     * @param object $entity
     *
     * @return bool
     */
    public function isSatisfiedBy($entity): bool;
}
