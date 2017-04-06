<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class Currency implements OptionInterface
{
    const CURRENCY = 'Currency';

    const AUSTRALIAN_DOLLAR = 'AUD';
    const US_DOLLAR = 'USD';
    const CANADIAN_DOLLAR = 'CAD';
    const EURO = 'EUR';
    const BRITISH_POUND = 'GBP';
    const NEW_ZEALAND_DOLLAR = 'NZD';

    /**
     * @var array
     */
    public static $currencies = [
        Currency::AUSTRALIAN_DOLLAR,
        Currency::US_DOLLAR,
        Currency::CANADIAN_DOLLAR,
        Currency::EURO,
        Currency::BRITISH_POUND,
        Currency::NEW_ZEALAND_DOLLAR,
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

    /** {@inheritdoc} */
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
