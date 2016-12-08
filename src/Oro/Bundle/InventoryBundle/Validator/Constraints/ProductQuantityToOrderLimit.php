<?php

namespace Oro\Bundle\InventoryBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ProductQuantityToOrderLimit extends Constraint
{
    public $message = 'oro.inventory.product.validators.quantity_min_over_max';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
