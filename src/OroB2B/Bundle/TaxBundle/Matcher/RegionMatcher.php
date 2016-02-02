<?php

namespace OroB2B\Bundle\TaxBundle\Matcher;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

class RegionMatcher extends AbstractMatcher
{
    /**
     * @var array
     */
    protected static $unitedStatesWithNonTaxableDigitalCodes = [
        'CA', 'DC', 'FL', 'GA', 'IA', 'IL', 'KS',
        'MD', 'MA', 'MI', 'MN', 'NY', 'NV', 'ND',
        'OH', 'OK', 'PA', 'RI', 'SC', 'VA', 'WV'
    ];

    /**
     * @var MatcherInterface
     */
    protected $countryMatcher;

    /**
     * {@inheritdoc}
     */
    public function match(AbstractAddress $address)
    {
        $country = $address->getCountry();
        $region = $address->getRegion();
        $regionText = $address->getRegionText();

        $countryTaxRules = $this->countryMatcher->match($address);

        if (null === $country || (null === $region && empty($regionText))) {
            return $countryTaxRules;
        }

        $regionTaxRules = $this->getTaxRuleRepository()->findByCountryAndRegion(
            $country,
            $region,
            $regionText
        );

        return $this->mergeResult($countryTaxRules, $regionTaxRules);
    }

    /**
     * @param MatcherInterface $countryMatcher
     */
    public function setCountryMatcher(MatcherInterface $countryMatcher)
    {
        $this->countryMatcher = $countryMatcher;
    }

    /**
     * @param AbstractAddress $address
     * @return bool
     */
    public function isStateWithNonTaxableDigitals(AbstractAddress $address)
    {
        return (CountryMatcher::COUNTRY_CODE_USA == $address->getCountry()->getIso2Code()) &&
            (in_array($address->getRegion()->getCode(), self::$unitedStatesWithNonTaxableDigitalCodes, true));
    }
}
