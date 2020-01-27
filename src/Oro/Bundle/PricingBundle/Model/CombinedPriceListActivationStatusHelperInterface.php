<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;

/**
 * Provide information about activation status for a given Combined Price List.
 */
interface CombinedPriceListActivationStatusHelperInterface
{
    /**
     * @param CombinedPriceList $cpl
     * @return bool
     */
    public function isReadyForBuild(CombinedPriceList $cpl): bool;

    /**
     * @return \DateTime
     */
    public function getActivateDate(): \DateTime;
}
