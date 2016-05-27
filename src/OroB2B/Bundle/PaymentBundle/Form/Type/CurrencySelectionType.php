<?php

namespace OroB2B\Bundle\PaymentBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\AbstractCurrencySelectionType;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\Currency;

class CurrencySelectionType extends AbstractCurrencySelectionType
{
    const NAME = 'orob2b_payment_currency_selection';
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

    /**
     * {@inheritdoc}
     */
    protected function getCurrencies()
    {
        $currencies = $this->configManager->get($this->getCurrencySelectorConfigKey());
        $supportedCurrencies = Currency::$currencies;

        return array_intersect($currencies, $supportedCurrencies);
    }
}
