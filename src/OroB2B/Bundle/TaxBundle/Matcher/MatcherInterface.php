<?php

namespace OroB2B\Bundle\TaxBundle\Matcher;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

use OroB2B\Bundle\TaxBundle\Entity\TaxRule;

interface MatcherInterface
{
    /**
     * Find TaxRules by address
     *
     * @param AbstractAddress $address
     * @return TaxRule[]
     */
    public function match(AbstractAddress $address);
}
