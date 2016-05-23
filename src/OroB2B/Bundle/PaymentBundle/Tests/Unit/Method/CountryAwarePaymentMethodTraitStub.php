<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Method;

use OroB2B\Bundle\PaymentBundle\Method\CountryAwarePaymentMethodTrait;

class CountryAwarePaymentMethodTraitStub
{
    use CountryAwarePaymentMethodTrait;

    /** @var array */
    protected $allowedCountries;

    /** @var bool */
    protected $allAllowed;

    /**
     * @param array $allowedCountries
     * @param bool $allAllowed
     */
    public function __construct(array $allowedCountries = [], $allAllowed = false)
    {
        $this->allowedCountries = $allowedCountries;
        $this->allAllowed = $allAllowed;
    }

    /** {@inheritdoc} */
    protected function getAllowedCountries()
    {
        return $this->allowedCountries;
    }

    /** {@inheritdoc} */
    protected function isAllCountriesAllowed()
    {
        return $this->allAllowed;
    }
}
