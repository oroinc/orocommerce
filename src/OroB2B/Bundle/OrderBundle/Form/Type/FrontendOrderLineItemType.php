<?php

namespace OroB2B\Bundle\OrderBundle\Form\Type;

class FrontendOrderLineItemType extends AbstractOrderLineItemType
{
    const NAME = 'orob2b_order_line_item_frontend';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
