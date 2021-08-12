<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * The constraint that can be used to validate that a product unit exists in a list of available product units
 * for a product associated with the validating value.
 */
class ProductUnitExists extends Constraint
{
    /** @var string */
    public string $message = 'oro.product.productunit.invalid';

    /**
     * Defines whether the "sell" flag of the product unit precision should be checked or not.
     *
     * @var bool
     */
    public bool $sell = false;

    /**
     * The path to the product unit field.
     *
     * @var string
     */
    public string $path = '';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return ProductUnitExistsValidator::ALIAS;
    }

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
