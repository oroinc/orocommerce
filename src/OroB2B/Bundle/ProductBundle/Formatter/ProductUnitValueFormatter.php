<?php

namespace OroB2B\Bundle\ProductBundle\Formatter;

/**
 * @deprecated Use orob2b_product.formatter.unit_value and setTranslationPrefix to define you own service with needed
 * translation prefix
 */
class ProductUnitValueFormatter extends UnitValueFormatter
{
    /**
     * {@inheritdoc}
     */
    protected function getTranslationPrefix()
    {
        return 'orob2b.product_unit';
    }
}
