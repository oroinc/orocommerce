<?php

namespace Oro\Bundle\PricingBundle\Model;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;

/**
 * Checks whether it is possible to build prices for a combined price list using delegation to specific handlers.
 */
class CombinedPriceListStatusHandler implements CombinedPriceListStatusHandlerInterface
{
    /** @var iterable|CombinedPriceListStatusHandlerInterface[]  */
    private iterable $handlers;

    public function __construct(iterable $handlers)
    {
        $this->handlers = $handlers;
    }

    /**
     * Building is possible if at least one of the handlers returns a positive result.
     */
    public function isReadyForBuild(CombinedPriceList $cpl): bool
    {
        foreach ($this->handlers as $handler) {
            if ($handler->isReadyForBuild($cpl)) {
                return true;
            }
        }

        return false;
    }
}
