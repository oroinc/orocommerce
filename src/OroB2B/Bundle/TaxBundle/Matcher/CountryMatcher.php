<?php

namespace OroB2B\Bundle\TaxBundle\Matcher;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

class CountryMatcher extends AbstractMatcher
{
    /**
     * @var array
     */
    protected $europeanUnionCountryCodes = [
        'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK',
        'EE', 'FI', 'FR', 'DE', 'EL', 'HU', 'IE',
        'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL',
        'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'UK'
    ];

    /**
     * {@inheritdoc}
     */
    public function match(AbstractAddress $address)
    {
        $country = $address->getCountry();

        if (null === $country) {
            return [];
        }

        return $this->getTaxRuleRepository()->findByCountry($country);
    }

    /**
     * @param string $countryCode
     * @return bool
     */
    public function isEuropeanUnionCountry($countryCode)
    {
        return in_array($countryCode, $this->europeanUnionCountryCodes);
    }
}
