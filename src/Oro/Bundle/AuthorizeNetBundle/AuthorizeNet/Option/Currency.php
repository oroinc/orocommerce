<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class Currency implements OptionsDependentInterface
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

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(Currency::CURRENCY)
            ->addAllowedValues(Currency::CURRENCY, Currency::$currencies);
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicableDependent(array $options)
    {
        if (!isset($options[Transaction::TRANSACTION_TYPE])) {
            return false;
        }

        return in_array(
            $options[Transaction::TRANSACTION_TYPE],
            [Transaction::CHARGE, Transaction::AUTHORIZE],
            true
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureDependentOption(OptionsResolver $resolver, array $options)
    {
        $this->configureOption($resolver);
    }
}
