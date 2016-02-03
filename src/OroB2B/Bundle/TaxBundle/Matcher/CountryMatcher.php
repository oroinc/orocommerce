<?php

namespace OroB2B\Bundle\TaxBundle\Matcher;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

class CountryMatcher extends AbstractMatcher
{
    /**
     * {@inheritdoc}
     */
    public function match(AbstractAddress $address)
    {
        $country = $address->getCountry();

        if (null === $country) {
            return [];
        }

        $cacheKey = $this->getCacheKey($country);
        if (array_key_exists($cacheKey, $this->taxRulesCache)) {
            return $this->taxRulesCache[$cacheKey];
        }

        $this->taxRulesCache[$cacheKey] = $this->getTaxRuleRepository()->findByCountry($country);

        return $this->taxRulesCache[$cacheKey];
    }
}
