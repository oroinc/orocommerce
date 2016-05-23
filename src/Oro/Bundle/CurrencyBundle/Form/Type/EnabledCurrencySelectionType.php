<?php

namespace Oro\Bundle\CurrencyBundle\Form\Type;

class EnabledCurrencySelectionType extends AbstractCurrencySelectionType
{
    const NAME = 'oro_enabled_currency_selection';
    const CURRENCY_SELECTOR_CONFIG_KEY = 'oro_b2b_pricing.enabled_currencies';

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
