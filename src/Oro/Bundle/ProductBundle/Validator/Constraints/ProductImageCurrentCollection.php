<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

/**
 * Constraint that checks image type by count with existing product images
 */
class ProductImageCurrentCollection extends ProductImageCollection
{
    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
