<?php

namespace OroB2B\Bundle\TaxBundle\Matcher;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

use OroB2B\Bundle\TaxBundle\Entity\TaxRule;

class ZipCodeMatcher extends AbstractMatcher
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
        $regionText = $address->getRegion() ? null : $address->getRegionText();
        $country = $address->getRegion() ? null : $address->getCountry();

        $countryTaxRules = $this->countryMatcher->match($address);
        // TODO: Add regionMatcher usage
        $zipCodeTaxRules = $this->getTaxRuleRepository()->findByZipCode(
            $address->getPostalCode(),
            $address->getRegion(),
            $regionText,
            $country
        );

        $result = [];
        /** @var TaxRule $taxRule */
        foreach (array_merge($countryTaxRules, $zipCodeTaxRules) as $taxRule) {
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
