<?php

namespace Oro\Bundle\InventoryBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check whether the product's maximum inventory quantity is higher or equal to
 * the product's minimum inventory quantity.
 */
class ProductQuantityToOrderLimit extends Constraint
{
    public $message = 'oro.inventory.product.validators.quantity_min_over_max';

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
