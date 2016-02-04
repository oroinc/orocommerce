<?php

namespace OroB2B\Bundle\TaxBundle\Matcher;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

class RegionMatcher extends AbstractMatcher
{
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
        $cacheKey = $this->getCacheKey($country, $region, $regionText);

        if (array_key_exists($cacheKey, $this->taxRulesCache)) {
            return $this->taxRulesCache[$cacheKey];
        }

        $countryTaxRules = $this->countryMatcher->match($address);

        if (null === $country || (null === $region && empty($regionText))) {
            return $countryTaxRules;
        }

        $regionTaxRules = $this->getTaxRuleRepository()->findByCountryAndRegion(
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
}
