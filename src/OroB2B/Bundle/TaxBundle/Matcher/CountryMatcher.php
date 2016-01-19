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
        return $this->getTaxRuleRepository()->findByCountry($address->getCountry());
    }
}
