<?php

namespace Oro\Bundle\ProductBundle\Formatter;

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
        return 'oro.product_unit';
    }
}
