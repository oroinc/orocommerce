<?php

namespace OroB2B\Bundle\TaxBundle\Matcher;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

class CountryMatcher extends AbstractMatcher
{
    const COUNTRY_CODE_USA = 'US';

    /**
     * {@inheritdoc}
     */
    public function match(AbstractAddress $address)
    {
        $country = $address->getCountry();

        if (null === $country) {
            return [];
        }

        return $this->getTaxRuleRepository()->findByCountry($country);
    }
}
