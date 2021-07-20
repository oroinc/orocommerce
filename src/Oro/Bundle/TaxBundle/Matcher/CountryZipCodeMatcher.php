<?php

namespace Oro\Bundle\TaxBundle\Matcher;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\TaxBundle\Model\TaxCodes;

/**
 * Match tax based on a given Country and Zip code, Region ignored.
 */
class CountryZipCodeMatcher extends AbstractMatcher
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
        $zipCode = $address->getPostalCode();

        $cacheKey = $this->getCacheKey($country, $zipCode, $taxCodes->getHash());
        if (array_key_exists($cacheKey, $this->taxRulesCache)) {
            return $this->taxRulesCache[$cacheKey];
        }

        $countryTaxRules = $this->countryMatcher->match($address, $taxCodes);

        if (null === $country || null === $zipCode || !$taxCodes->isFullFilledTaxCode()) {
            return $countryTaxRules;
        }

        $zipCodeTaxRules = $this->getTaxRuleRepository()->findByCountryAndZipCodeAndTaxCode(
            $taxCodes,
            $zipCode,
            $country
        );

        $this->taxRulesCache[$cacheKey] = $this->mergeResult($countryTaxRules, $zipCodeTaxRules);

        return $this->taxRulesCache[$cacheKey];
    }

    public function setCountryMatcher(MatcherInterface $countryMatcher)
    {
        $this->countryMatcher = $countryMatcher;
    }
}
