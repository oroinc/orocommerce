<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

/**
 * Configures currency option for PayPal Payflow transactions.
 *
 * Specifies the transaction currency code (USD, EUR, GBP, etc.),
 * with configurable requirement based on transaction type.
 */
class Currency implements OptionInterface
{
    public const CURRENCY = 'CURRENCY';

    public const AUSTRALIAN_DOLLAR = 'AUD';
    public const CANADIAN_DOLLAR = 'CAD';
    public const EURO = 'EUR';
    public const BRITISH_POUND = 'GBP';
    public const JAPANESE_YEN = 'JPY';
    public const US_DOLLAR = 'USD';

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

    /** @var bool */
    protected $required;

    /**
     * @param bool $required
     */
    public function __construct($required = true)
    {
        $this->required = $required;
    }

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        if ($this->required) {
            $resolver
                ->setRequired(Currency::CURRENCY);
        }

        $resolver
            ->setDefined(Currency::CURRENCY)
            ->addAllowedValues(Currency::CURRENCY, Currency::$currencies);
    }
}
