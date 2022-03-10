<?php

namespace Oro\Bundle\PricingBundle\Resolver;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;

/**
 * Determines the active combined price list.
 */
class ActiveCombinedPriceListResolver
{
    private ManagerRegistry $managerRegistry;
    private CombinedPriceListScheduleResolver $combinedPriceListScheduleResolver;

    public function __construct(
        ManagerRegistry $managerRegistry,
        CombinedPriceListScheduleResolver $combinedPriceListScheduleResolver
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->combinedPriceListScheduleResolver = $combinedPriceListScheduleResolver;
    }

    public function getActiveCplByFullCPL(CombinedPriceList $fullCpl): CombinedPriceList
    {
        if ($this->isScheduledCpl($fullCpl)) {
            $activeCpl = $this->combinedPriceListScheduleResolver->getActiveCplByFullCPL($fullCpl);

            return $activeCpl ?? $fullCpl;
        }

        return $fullCpl;
    }

    private function isScheduledCpl(CombinedPriceList $cpl): bool
    {
        return $this->managerRegistry
            ->getRepository(CombinedPriceListActivationRule::class)
            ->hasActivationRules($cpl);
    }
}
