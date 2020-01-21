<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;

/**
 * Helper methods for checking Combined Price List connections
 */
interface CombinedPriceListRelationHelperInterface
{
    /**
     * @param CombinedPriceList $cpl
     * @return bool
     */
    public function isFullChainCpl(CombinedPriceList $cpl): bool;
}
