<?php

namespace Oro\Bundle\PayPalBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType as BaseCurrencySelectionType;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\Currency;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CurrencySelectionType extends BaseCurrencySelectionType
{
    const NAME = 'oro_paypal_currency_selection';

    /**
     * {@inheritdoc}
     */
    protected function getCurrencies()
    {
        $currencies = parent::getCurrencies();

        return array_intersect($currencies, Currency::$currencies);
    }

    /**
     * @inheritDoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('full_currency_name', true);
    }
}
