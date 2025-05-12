<?php

namespace Oro\Bundle\OrderBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * This constraint is used to check that the order discount type is valid.
 */
class DiscountType extends Constraint
{
    public string $message = 'oro.order.discounts.type.error.label';
}
