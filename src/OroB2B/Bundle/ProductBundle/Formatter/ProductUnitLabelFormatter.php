<?php

namespace OroB2B\Bundle\ProductBundle\Formatter;

/**
 * @deprecated
 */
class ProductUnitLabelFormatter extends UnitLabelFormatter
{
    /**
     * {@inheritdoc}
     */
    public function getTranslationPrefix()
    {
        return 'orob2b.product_unit';
    }
}
