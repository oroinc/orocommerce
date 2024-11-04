<?php

namespace Oro\Bundle\OrderBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for checking if either a product or freeFormProduct field is filled in {@see OrderLineItem}.
 */
class LineItemProduct extends Constraint
{
    /**
     * @var string
     */
    public $emptyProductMessage = 'oro.order.orderlineitem.product.blank';

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
