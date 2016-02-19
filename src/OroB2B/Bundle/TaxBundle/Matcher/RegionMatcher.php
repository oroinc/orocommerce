<?php

namespace OroB2B\Bundle\TaxBundle\Matcher;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use OroB2B\Bundle\TaxBundle\Model\TaxCodes;

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
    public function match(AbstractAddress $address, TaxCodes $taxCodes)
    {
        $country = $address->getCountry();
        $region = $address->getRegion();
        $regionText = $address->getRegionText();
        $cacheKey = $this->getCacheKey($country, $region, $regionText, $taxCodes->getHash());

        if (array_key_exists($cacheKey, $this->taxRulesCache)) {
            return $this->taxRulesCache[$cacheKey];
        }

        $countryTaxRules = $this->countryMatcher->match($address, $taxCodes);

        if (null === $country || (null === $region && empty($regionText))
        ) {
            return $countryTaxRules;
        }

        $regionTaxRules = $this->getTaxRuleRepository()->findByRegionAndTaxCode(
            $taxCodes,
            $country,
            $region,
            $regionText
        );

        $this->taxRulesCache[$cacheKey] = $this->mergeResult($countryTaxRules, $regionTaxRules);

        return $this->taxRulesCache[$cacheKey];
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
