<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;

class DefaultCurrencySelectionType extends CurrencySelectionType
{
    const NAME = 'orob2b_pricing_default_currency_selection';

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
