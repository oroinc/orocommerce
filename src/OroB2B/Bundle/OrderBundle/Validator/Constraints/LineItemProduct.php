<?php

namespace OroB2B\Bundle\OrderBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class LineItemProduct extends Constraint
{
    /**
     * @var string
     */
    public $emptyProductMessage = 'orob2b.order.orderlineitem.product.blank';

    /**
     * @var string
     */
    public $priceNotFoundMessage = 'orob2b.order.orderlineitem.product_price.blank';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
