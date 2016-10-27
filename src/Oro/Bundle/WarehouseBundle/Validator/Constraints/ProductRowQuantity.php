<?php

namespace Oro\Bundle\WarehouseBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ProductRowQuantity extends Constraint
{
    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
