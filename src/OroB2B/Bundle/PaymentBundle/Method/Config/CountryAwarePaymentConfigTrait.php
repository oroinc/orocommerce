<?php

namespace Oro\Bundle\PaymentBundle\Method\Config;

trait CountryAwarePaymentConfigTrait
{
    /**
     * @return bool
     */
    abstract public function isAllCountriesAllowed();

    /**
     * @return array
     */
    abstract public function getAllowedCountries();

    /**
     * @param array $context
     * @return bool
     */
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
}
