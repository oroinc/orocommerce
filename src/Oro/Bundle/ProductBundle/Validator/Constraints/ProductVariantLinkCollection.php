<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validate uninitialized variant links collection.
 */
class ProductVariantLinkCollection extends Constraint
{
    public ?array $constraints = null;

    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
