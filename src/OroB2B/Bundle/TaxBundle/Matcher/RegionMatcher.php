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
    public function match(AbstractAddress $address, $productTaxCode, $accountTaxCode)
    {
        $country = $address->getCountry();
        $region = $address->getRegion();
        $regionText = $address->getRegionText();

        $countryTaxRules = $this->countryMatcher->match($address, $productTaxCode, $accountTaxCode);

        if (null === $productTaxCode ||
            null === $accountTaxCode ||
            null === $country ||
            (null === $region && empty($regionText))
        ) {
            return $countryTaxRules;
        }

        $regionTaxRules = $this->getTaxRuleRepository()->findByCountryAndRegionAndProductTaxCodeAndAccountTaxCode(
            $productTaxCode,
            $accountTaxCode,
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
