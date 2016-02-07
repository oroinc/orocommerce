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
     * @param string $productTaxCode
     * @param string $accountTaxCode
     * @return TaxRule[]
     */
    public function match(AbstractAddress $address, $productTaxCode, $accountTaxCode);
}
