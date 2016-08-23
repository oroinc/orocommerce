<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\AbstractCurrencySelectionType;

class EnabledCurrencySelectionType extends AbstractCurrencySelectionType
{
    const NAME = 'orob2b_pricing_enabled_currency_selection';
    const CURRENCY_SELECTOR_CONFIG_KEY = 'oro_b2b_pricing.enabled_currencies';

      /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
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
