<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class Currency implements OptionInterface
{
    const CURRENCY = 'CURRENCY';

    const AUSTRALIAN_DOLLAR = 'AUD';
    const CANADIAN_DOLLAR = 'CAD';
    const EURO = 'EUR';
    const BRITISH_POUND = 'GBP';
    const JAPANESE_YEN = 'JPY';
    const US_DOLLAR = 'USD';

    /**
     * @var array
     */
    public static $currencies = [
        Currency::AUSTRALIAN_DOLLAR,
        Currency::CANADIAN_DOLLAR,
        Currency::EURO,
        Currency::BRITISH_POUND,
        Currency::JAPANESE_YEN,
        Currency::US_DOLLAR,
    ];

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(Currency::CURRENCY)
            ->addAllowedValues(Currency::CURRENCY, Currency::$currencies);
    }
}
