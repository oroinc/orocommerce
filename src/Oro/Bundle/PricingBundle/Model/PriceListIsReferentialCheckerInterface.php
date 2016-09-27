<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\PricingBundle\Entity\PriceList;

interface PriceListIsReferentialCheckerInterface
{
    /**
     * @param PriceList $object
     * @return bool
     */
    public function isReferential(PriceList $object);
}
