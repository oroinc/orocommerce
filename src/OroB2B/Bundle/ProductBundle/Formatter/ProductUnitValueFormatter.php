<?php

namespace OroB2B\Bundle\ProductBundle\Formatter;

/**
 * @deprecated
 */
class ProductUnitValueFormatter extends UnitValueFormatter
{
    /**
     * {@inheritdoc}
     */
    public function getTranslationPrefix()
    {
        return 'orob2b.product_unit';
    }
}
