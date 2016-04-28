<?php

namespace OroB2B\Bundle\ShippingBundle\Formatter;

use OroB2B\Bundle\ProductBundle\Formatter\AbstractUnitValueFormatter;

class WeightUnitValueFormatter extends AbstractUnitValueFormatter
{
    /**
     * {@inheritdoc}
     */
    protected function getTranslationPrefix()
    {
        return 'orob2b.weight_unit';
    }
}
