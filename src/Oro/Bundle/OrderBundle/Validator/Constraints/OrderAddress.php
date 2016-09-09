<?php

namespace Oro\Bundle\OrderBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class OrderAddress extends Constraint implements ConstraintByValidationGroups
{
    /**
     * @var array
     */
    protected $validationGroups;

    /**
     * @return string
     */
    public function validatedBy()
    {
        return 'oro_order_address_validator';
    }

    /**
     * @return array
     */
    public function getValidationGroups()
    {
        return $this->validationGroups;
    }
}
