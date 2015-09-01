<?php

namespace OroB2B\Bundle\OrderBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class OrderLineItemCount extends Constraint
{
    /**
     * @var string
     */
    public $minLineItemCountMessage = 'orob2b.order.orderlineitem.count';

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'orob2b_order_line_items_count_validator';
    }

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
