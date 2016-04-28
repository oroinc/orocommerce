<?php

namespace OroB2B\Bundle\ProductBundle\Formatter;

class ProductUnitLabelFormatter extends AbstractLabelFormatter
{
    /**
     * {@inheritdoc}
     */
    protected function getTranslationPrefix()
    {
        return 'orob2b.product_unit';
    }
}
