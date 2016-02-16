<?php

namespace OroB2B\Bundle\TaxBundle\Matcher;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

class CountryMatcher extends AbstractMatcher
{
    /**
     * @var array
     */
    protected static $europeanUnionCountryCodes = [
        'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK',
        'EE', 'FI', 'FR', 'DE', 'EL', 'HU', 'IE',
        'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL',
        'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'UK'
    ];

    /**
     * {@inheritdoc}
     */
    public function match(AbstractAddress $address, $productTaxCode, $accountTaxCode)
    {
        $country = $address->getCountry();

        if (null === $country || $productTaxCode === null || $accountTaxCode === null) {
            return [];
        }

        $cacheKey = $this->getCacheKey($country, $productTaxCode);
        if (array_key_exists($cacheKey, $this->taxRulesCache)) {
            return $this->taxRulesCache[$cacheKey];
        }

        $this->taxRulesCache[$cacheKey] =
            $this->getTaxRuleRepository()->findByCountryAndProductTaxCodeAndAccountTaxCode(
                $country,
                $productTaxCode,
                $accountTaxCode
            );

        return $this->taxRulesCache[$cacheKey];
    }

    /**
     * @param string $countryCode
     * @return bool
     */
    public function isEuropeanUnionCountry($countryCode)
    {
        return in_array($countryCode, self::$europeanUnionCountryCodes, true);
    }
}
