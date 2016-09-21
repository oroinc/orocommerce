<?php

namespace Oro\Bundle\ProductBundle\Formatter;

/**
 * @deprecated Use oro_product.formatter.unit_value and setTranslationPrefix to define you own service with needed
 * translation prefix
 */
class ProductUnitValueFormatter extends UnitValueFormatter
{
    /**
     * {@inheritdoc}
     */
    protected function getTranslationPrefix()
    {
        return 'oro.product_unit';
    }
}
