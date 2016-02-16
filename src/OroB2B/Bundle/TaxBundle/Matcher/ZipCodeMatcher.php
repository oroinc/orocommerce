<?php

namespace OroB2B\Bundle\TaxBundle\Matcher;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

class ZipCodeMatcher extends AbstractMatcher
{
    /**
     * @var MatcherInterface
     */
    protected $regionMatcher;

    /**
     * {@inheritdoc}
     */
    public function match(AbstractAddress $address, $productTaxCode, $accountTaxCode)
    {
        $country = $address->getCountry();
        $region = $address->getRegion();
        $regionText = $address->getRegionText();
        $zipCode = $address->getPostalCode();

        $cacheKey = $this->getCacheKey($country, $region, $regionText, $zipCode, $productTaxCode);
        if (array_key_exists($cacheKey, $this->taxRulesCache)) {
            return $this->taxRulesCache[$cacheKey];
        }

        $regionTaxRules = $this->regionMatcher->match($address, $productTaxCode, $accountTaxCode);

        if (null === $productTaxCode ||
            null === $accountTaxCode ||
            null === $country ||
            (null === $region && empty($regionText))
        ) {
            return $regionTaxRules;
        }

        $zipCodeTaxRules = $this->getTaxRuleRepository()->findByZipCodeAndProductTaxCodeAndAccountTaxCode(
            $productTaxCode,
            $accountTaxCode,
            $address->getPostalCode(),
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
