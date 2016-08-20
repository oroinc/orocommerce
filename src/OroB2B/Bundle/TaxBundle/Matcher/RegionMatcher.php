<?php

namespace Oro\Bundle\TaxBundle\Matcher;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\TaxBundle\Model\TaxCodes;

class RegionMatcher extends AbstractMatcher
{
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

        if (null === $country || (null === $region && empty($regionText)) || !$taxCodes->isFullFilledTaxCode()
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
}
