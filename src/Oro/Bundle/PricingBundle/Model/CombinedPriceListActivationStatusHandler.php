<?php

namespace Oro\Bundle\PricingBundle\Model;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
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

    /**
     * @var ConfigManager
     */
    private $configManager;

    public function __construct(
        ManagerRegistry $registry,
        ConfigManager $configManager
    ) {
        $this->registry = $registry;
        $this->configManager = $configManager;
    }

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

        $activeRule = $cplActivationRepo->getActiveRuleByScheduledCpl($cpl, $activationDate);
        if ($activeRule) {
            return true;
        }

        return false;
    }

    private function getActivateDate(): \DateTime
    {
        $offsetHours = $this->configManager->get('oro_pricing.offset_of_processing_cpl_prices');
        $activateDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $activateDate->add(new \DateInterval(sprintf('PT%dM', $offsetHours * 60)));

        return $activateDate;
    }
}
