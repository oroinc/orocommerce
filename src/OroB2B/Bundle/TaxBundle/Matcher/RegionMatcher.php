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

        if (null === $country || (null === $region && empty($regionText))) {
            return [];
        }

        $countryTaxRules = $this->countryMatcher->match($address);
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
}
