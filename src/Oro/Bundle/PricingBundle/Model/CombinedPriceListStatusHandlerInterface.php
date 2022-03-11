<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;

/**
 * Provide information about activation status for a given Combined Price List.
 */
interface CombinedPriceListStatusHandlerInterface
{
    public function isReadyForBuild(CombinedPriceList $cpl): bool;
}
