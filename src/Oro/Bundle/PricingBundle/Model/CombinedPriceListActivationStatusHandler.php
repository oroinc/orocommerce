<?php

namespace Oro\Bundle\PricingBundle\Model;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListActivationRuleRepository;

/**
 * Provide information about activation status for a given Combined Price List.
 */
class CombinedPriceListActivationStatusHandler implements CombinedPriceListStatusHandlerInterface
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    public function __construct(
        ManagerRegistry $registry
    ) {
        $this->registry = $registry;
    }

    #[\Override]
    public function isReadyForBuild(CombinedPriceList $cpl): bool
    {
        $activationDate = new \DateTime('now', new \DateTimeZone('UTC'));
        /** @var CombinedPriceListActivationRuleRepository $cplActivationRepo */
        $cplActivationRepo = $this->registry
            ->getManagerForClass(CombinedPriceListActivationRule::class)
            ->getRepository(CombinedPriceListActivationRule::class);

        if (!$cplActivationRepo->hasActivationRules($cpl)) {
            return true;
        }

        return $cplActivationRepo->hasActiveRuleByScheduledCpl($cpl, $activationDate);
    }
}
