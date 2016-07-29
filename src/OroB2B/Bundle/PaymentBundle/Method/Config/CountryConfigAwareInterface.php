<?php

namespace OroB2B\Bundle\PaymentBundle\Method\Config;

interface CountryConfigAwareInterface
{
    /**
     * @return array
     */
    public function getAllowedCountries();

    /**
     * @return bool
     */
    public function isAllCountriesAllowed();

    /**
     * @param array $context
     * @return bool
     */
    public function isCountryApplicable(array $context = []);
}
