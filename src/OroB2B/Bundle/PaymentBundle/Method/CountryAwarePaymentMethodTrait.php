<?php

namespace OroB2B\Bundle\PaymentBundle\Method;

trait CountryAwarePaymentMethodTrait
{
    /** {@inheritdoc} */
    public function isCountryApplicable(array $context = [])
    {
        if ($this->isAllCountriesAllowed()) {
            return true;
        }

        if (!empty($context['country']) && $this->getAllowedCountries()) {
            return in_array($context['country'], $this->getAllowedCountries(), true);
        }

        return false;
    }

    /**
     * @return array
     */
    abstract protected function getAllowedCountries();

    /**
     * @return array
     */
    abstract protected function isAllCountriesAllowed();
}
