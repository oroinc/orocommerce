<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * The constraint that can be used to validate that a product unit exists in a list of available product units
 * for a product associated with the validating value.
 */
class ProductUnitExists extends Constraint
{
    public string $message = 'oro.product.productunit.invalid';

    /**
     * Defines whether the "sell" flag of the product unit precision should be checked or not.
     */
    public bool $sell = false;

    /**
     * The path to the product unit field.
     */
    public string $path = '';

    #[\Override]
    public function validatedBy(): string
    {
        return ProductUnitExistsValidator::ALIAS;
    }

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
