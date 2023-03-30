<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that only product with type {@see \Oro\Bundle\ProductBundle\Entity\Product::TYPE_SIMPLE}
 * can be used in kit options.
 */
class OnlySimpleProductsInProductKitItemProductsCollection extends Constraint
{
    public string $message = 'oro.product.productkititem.products.only_simple';

    public function getTargets(): array
    {
        return [self::CLASS_CONSTRAINT];
    }
}
