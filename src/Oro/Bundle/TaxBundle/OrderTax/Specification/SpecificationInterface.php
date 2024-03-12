<?php

namespace Oro\Bundle\TaxBundle\OrderTax\Specification;

/**
 * Base interface for the OrderTax specifications
 */
interface SpecificationInterface
{
    public function isSatisfiedBy(object $entity): bool;
}
