<?php

namespace Oro\Bundle\OrderBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class LineItemProduct extends Constraint
{
    /**
     * @var string
     */
    public $emptyProductMessage = 'oro.order.orderlineitem.product.blank';

    /**
     * @var string
     */
    public $priceNotFoundMessage = 'oro.order.orderlineitem.product_price.blank';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
