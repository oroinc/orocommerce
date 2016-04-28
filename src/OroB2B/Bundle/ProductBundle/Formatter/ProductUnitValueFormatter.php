<?php

namespace OroB2B\Bundle\ProductBundle\Formatter;

class ProductUnitValueFormatter extends AbstractUnitValueFormatter
{
    /**
     * {@inheritdoc}
     */
    protected function getTranslationPrefix()
    {
        return 'orob2b.product_unit';
    }
}
