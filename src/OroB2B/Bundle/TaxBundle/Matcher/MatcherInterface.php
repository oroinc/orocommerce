<?php

namespace Oro\Bundle\TaxBundle\Matcher;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Model\TaxCodes;

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
