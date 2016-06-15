<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Method;

use OroB2B\Bundle\PaymentBundle\Method\CurrencyAwarePaymentMethodTrait;

class CurrencyAwarePaymentMethodTraitStub
{
    use CurrencyAwarePaymentMethodTrait;

    /**
     * @var array
     */
    protected $allowedCurrencies;

    /**
     * @param array $allowedCountries
     */
    public function __construct(array $allowedCountries = [])
    {
        $this->allowedCurrencies = $allowedCountries;
    }

    /** {@inheritdoc} */
    protected function getAllowedCurrencies()
    {
        return $this->allowedCurrencies;
    }
}
