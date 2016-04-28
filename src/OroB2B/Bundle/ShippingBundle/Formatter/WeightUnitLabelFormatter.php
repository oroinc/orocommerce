<?php

namespace OroB2B\Bundle\ShippingBundle\Formatter;

use OroB2B\Bundle\ProductBundle\Formatter\AbstractLabelFormatter;

class WeightUnitLabelFormatter extends AbstractLabelFormatter
{
    /**
     * {@inheritdoc}
     */
    protected function getTranslationPrefix()
    {
        return 'orob2b.weight_unit';
    }
}
