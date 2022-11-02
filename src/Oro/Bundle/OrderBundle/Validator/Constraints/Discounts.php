<?php

namespace Oro\Bundle\OrderBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check that the sum of all order discounts does not exceed the order grand total amount.
 */
class Discounts extends Constraint
{
    public $errorMessage = 'oro.order.discounts.sum.error.label';

    /**
     * {@inheritDoc}
     */
    public function getTargets()
    {
        return [self::CLASS_CONSTRAINT, self::PROPERTY_CONSTRAINT];
    }
}
