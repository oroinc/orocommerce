<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class Currency implements OptionInterface
{
    const CURRENCY = 'currency';

    const AUSTRALIAN_DOLLAR = 'AUD';
    const US_DOLLAR = 'USD';
    const CANADIAN_DOLLAR = 'CAD';
    const EURO = 'EUR';
    const BRITISH_POUND = 'GBP';
    const NEW_ZEALAND_DOLLAR = 'NZD';

    const ALL_CURRENCIES = [
        Currency::AUSTRALIAN_DOLLAR,
        Currency::US_DOLLAR,
        Currency::CANADIAN_DOLLAR,
        Currency::EURO,
        Currency::BRITISH_POUND,
        Currency::NEW_ZEALAND_DOLLAR,
    ];

    /**
     * @var bool
     */
    protected $requiredOption;

    /**
     * @param bool $requiredOption
     */
    public function __construct($requiredOption = true)
    {
        $this->requiredOption = $requiredOption;
    }


    /**
     * {@inheritdoc}
     */
    public function configureOption(OptionsResolver $resolver)
    {
        if ($this->requiredOption) {
            $resolver->setRequired(Currency::CURRENCY);
        }

        $resolver
            ->setDefined(Currency::CURRENCY)
            ->addAllowedValues(Currency::CURRENCY, Currency::ALL_CURRENCIES);
    }
}
