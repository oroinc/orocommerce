<?php

namespace Oro\Bundle\TaxBundle\Matcher;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\TaxBundle\Model\TaxCodes;

class CountryMatcher extends AbstractMatcher
{
    /**
     * {@inheritdoc}
     */
    public function match(AbstractAddress $address, TaxCodes $taxCodes)
    {
        $country = $address->getCountry();

        if (null === $country || !$taxCodes->isFullFilledTaxCode()) {
            return [];
        }

        $cacheKey = $this->getCacheKey($country, $taxCodes->getHash());
        if (array_key_exists($cacheKey, $this->taxRulesCache)) {
            return $this->taxRulesCache[$cacheKey];
        }

        $this->taxRulesCache[$cacheKey] =
            $this->getTaxRuleRepository()->findByCountryAndTaxCode($taxCodes, $country);

        return $this->taxRulesCache[$cacheKey];
    }
}
