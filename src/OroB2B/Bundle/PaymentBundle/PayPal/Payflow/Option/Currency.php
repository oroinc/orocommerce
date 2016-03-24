<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

use Symfony\Component\OptionsResolver\OptionsResolver;

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
        self::AUSTRALIAN_DOLLAR,
        self::CANADIAN_DOLLAR,
        self::EURO,
        self::BRITISH_POUND,
        self::JAPANESE_YEN,
        self::US_DOLLAR,
    ];

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined([Currency::CURRENCY])
            ->addAllowedValues(Currency::CURRENCY, Currency::$currencies);
    }
}
