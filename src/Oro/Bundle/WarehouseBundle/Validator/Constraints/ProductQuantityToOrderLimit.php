<?php

namespace Oro\Bundle\WarehouseBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ProductQuantityToOrderLimit extends Constraint
{
    public $message = 'oro.warehouse.product.validators.quantity_min_over_max';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
