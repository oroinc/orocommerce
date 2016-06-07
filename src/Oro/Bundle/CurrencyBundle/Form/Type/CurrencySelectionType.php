<?php

namespace Oro\Bundle\CurrencyBundle\Form\Type;

class CurrencySelectionType extends AbstractCurrencySelectionType
{
    const NAME = 'oro_currency_selection';
    const CURRENCY_SELECTOR_CONFIG_KEY = 'oro_currency.allowed_currencies';

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrencySelectorConfigKey()
    {
        return static::CURRENCY_SELECTOR_CONFIG_KEY;
    }
}
