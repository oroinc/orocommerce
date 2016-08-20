<?php

namespace Oro\Bundle\TaxBundle\Matcher;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\TaxBundle\Model\TaxCodes;

class ZipCodeMatcher extends AbstractMatcher
{
    /**
     * @var MatcherInterface
     */
    protected $regionMatcher;

    /**
     * {@inheritdoc}
     */
    public function match(AbstractAddress $address, TaxCodes $taxCodes)
    {
        $country = $address->getCountry();
        $region = $address->getRegion();
        $regionText = $address->getRegionText();
        $zipCode = $address->getPostalCode();

        $cacheKey = $this->getCacheKey($country, $region, $regionText, $zipCode, $taxCodes->getHash());
        if (array_key_exists($cacheKey, $this->taxRulesCache)) {
            return $this->taxRulesCache[$cacheKey];
        }

        $regionTaxRules = $this->regionMatcher->match($address, $taxCodes);

        if (null === $country || (null === $region && empty($regionText)) || !$taxCodes->isFullFilledTaxCode()) {
            return $regionTaxRules;
        }

        $zipCodeTaxRules = $this->getTaxRuleRepository()->findByZipCodeAndTaxCode(
            $taxCodes,
            $zipCode,
            $country,
            $region,
            $regionText
        );

        $this->taxRulesCache[$cacheKey] = $this->mergeResult($regionTaxRules, $zipCodeTaxRules);

        return $this->taxRulesCache[$cacheKey];
    }

    /**
     * @param MatcherInterface $regionMatcher
     */
    public function setRegionMatcher(MatcherInterface $regionMatcher)
    {
        $this->regionMatcher = $regionMatcher;
    }
}
