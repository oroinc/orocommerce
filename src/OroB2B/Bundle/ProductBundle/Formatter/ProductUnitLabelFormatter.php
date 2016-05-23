<?php

namespace OroB2B\Bundle\ProductBundle\Formatter;

/**
 * @deprecated Use orob2b_product.formatter.unit_label and setTranslationPrefix to define you own service with needed
 * translation prefix
 */
class ProductUnitLabelFormatter extends UnitLabelFormatter
{
    /**
     * {@inheritdoc}
     */
    protected function getTranslationPrefix()
    {
        return 'orob2b.product_unit';
    }
}
