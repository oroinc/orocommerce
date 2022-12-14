<?php

namespace Oro\Bundle\TaxBundle\Matcher;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Model\TaxCodes;

/**
 * Represents a service to finds tax rules by an address.
 */
interface MatcherInterface
{
    /**
     * Finds tax rules by an address.
     *
     * @param AbstractAddress $address
     * @param TaxCodes        $taxCodes
     *
     * @return TaxRule[]
     */
    public function match(AbstractAddress $address, TaxCodes $taxCodes): array;
}
