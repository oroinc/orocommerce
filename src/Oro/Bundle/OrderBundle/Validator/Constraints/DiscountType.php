<?php

namespace Oro\Bundle\OrderBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class DiscountType extends Constraint
{
    /**
     * @var string
     */
    public $errorMessage = 'oro.order.discounts.type.error.label';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
