<?php

namespace OroB2B\Bundle\SaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

class QuoteProductToOrderType extends AbstractType
{
    const NAME = 'orob2b_sale_quote_product_to_order';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
