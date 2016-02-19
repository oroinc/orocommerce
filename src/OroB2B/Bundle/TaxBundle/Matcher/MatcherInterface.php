<?php

namespace OroB2B\Bundle\TaxBundle\Matcher;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

use OroB2B\Bundle\TaxBundle\Entity\TaxRule;
use OroB2B\Bundle\TaxBundle\Model\TaxCodes;

interface MatcherInterface
{
    /**
     * Find TaxRules by address
     *
     * @param AbstractAddress $address
     * @param TaxCodes $taxCodes
     * @return TaxRule[]
     */
    public function match(AbstractAddress $address, TaxCodes $taxCodes);
}
