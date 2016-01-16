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
        $countryTaxRules = $this->countryMatcher->match($address);
        $regionTaxRules = $this->getTaxRuleRepository()->findByCountryAndRegion(
            $address->getCountry(),
            $address->getRegion(),
            $address->getRegionText()
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
