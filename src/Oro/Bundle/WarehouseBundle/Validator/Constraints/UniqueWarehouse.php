<?php

namespace Oro\Bundle\WarehouseBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueWarehouse extends Constraint
{
    /**
     * @var string
     */
    protected $message = 'oro.warehouse.validators.unique_warehouse.message';

    /**
     * @return string
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }
}
