<?php

namespace OroB2B\Bundle\TaxBundle\Matcher;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

class CountryMatcher extends AbstractMatcher
{
    const COUNTRY_CODE_USA = 'US';

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
    public function match(AbstractAddress $address, $productTaxCode)
    {
        $country = $address->getCountry();

        if (null === $country || $productTaxCode === null) {
            return [];
        }

        return $this->getTaxRuleRepository()->findByCountryAndProductTaxCode($country, $productTaxCode);
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
