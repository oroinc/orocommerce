<?php

namespace OroB2B\Bundle\TaxBundle\Matcher;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

use OroB2B\Bundle\TaxBundle\Entity\TaxRule;

class RegionMatcher extends AbstractMatcher
{
    /**
     * @var AbstractMatcher
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

        $result = [];
        /** @var TaxRule $taxRule */
        foreach (array_merge($countryTaxRules, $regionTaxRules) as $taxRule) {
            if (!array_key_exists($taxRule->getId(), $result)) {
                $result[$taxRule->getId()] = $taxRule;
            }
        }
        return array_values($result);
    }

    /**
     * @param AbstractMatcher $countryMatcher
     * @todo replace AbstractMatcher on MatcherInterface
     */
    public function setCountryMatcher(AbstractMatcher $countryMatcher)
    {
        $this->countryMatcher = $countryMatcher;
    }
}
