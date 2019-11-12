<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint of validator that validates that product unit can be set for given product.
 */
class ProductLineItem extends Constraint
{
    public $message = 'oro.product.productunit.not_applicable';

    /**
     * Should the product unit precision be checked on sell or not.
     *
     * @var bool
     */
    public $sell;

    /**
     * Path to product line item field.
     *
     * @var string
     */
    public $path;

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
