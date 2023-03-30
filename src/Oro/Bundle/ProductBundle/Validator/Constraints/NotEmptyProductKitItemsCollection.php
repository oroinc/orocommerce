<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Checks for at least one Product Kit Item
 * for Product with type {@see \Oro\Bundle\ProductBundle\Entity\Product::TYPE_KIT}.
 */
class NotEmptyProductKitItemsCollection extends Constraint
{
    public string $message = 'oro.product.productkititem.collection.not_empty';

    public function getTargets(): array
    {
        return [self::CLASS_CONSTRAINT];
    }
}
