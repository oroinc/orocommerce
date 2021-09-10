<?php

namespace Oro\Bundle\PricingBundle\Builder;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListActivationRuleRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;

/**
 * Remove unused Combined Price Lists.
 * Combined Price List considered as unused when it is not associated with any entity and has no actual activation plan
 */
class CombinedPriceListGarbageCollector
{
    /**
     * @var CombinedPriceListTriggerHandler
     */
    protected $triggerHandler;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    public function __construct(
        ManagerRegistry $registry,
        ConfigManager $configManager,
        CombinedPriceListTriggerHandler $triggerHandler
    ) {
        $this->registry = $registry;
        $this->configManager = $configManager;
        $this->triggerHandler = $triggerHandler;
    }

    public function cleanCombinedPriceLists()
    {
        $this->deleteInvalidRelations();
        $this->cleanActivationRules();
        $this->scheduleUnusedPriceListsRemoval();
    }

    private function deleteInvalidRelations(): void
    {
        $manager = $this->registry->getManager();
        $manager->getRepository(CombinedPriceListToCustomer::class)->deleteInvalidRelations();
        $manager->getRepository(CombinedPriceListToCustomerGroup::class)->deleteInvalidRelations();
        $manager->getRepository(CombinedPriceListToWebsite::class)->deleteInvalidRelations();
    }

    private function cleanActivationRules()
    {
        /** @var CombinedPriceListActivationRuleRepository $repo */
        $repo = $this->registry
            ->getManagerForClass(CombinedPriceListActivationRule::class)
            ->getRepository(CombinedPriceListActivationRule::class);

        $repo->deleteExpiredRules(new \DateTime('now', new \DateTimeZone('UTC')));

        $exceptPriceLists = $this->getConfigFullChainPriceLists();
        $repo->deleteUnlinkedRules($exceptPriceLists);
    }

    private function scheduleUnusedPriceListsRemoval(): void
    {
        /** @var CombinedPriceListRepository $cplRepository */
        $cplRepository = $this->registry
            ->getManagerForClass(CombinedPriceList::class)
            ->getRepository(CombinedPriceList::class);

        $exceptPriceLists = $this->getAllConfigPriceLists();
        $priceListsForDelete = $cplRepository->getUnusedPriceListsIds($exceptPriceLists);
        $this->triggerHandler->startCollect();
        $this->triggerHandler->massProcess($priceListsForDelete);
        $cplRepository->deletePriceLists($priceListsForDelete);
        $this->triggerHandler->commit();
    }

    private function getConfigFullChainPriceLists(): array
    {
        $exceptPriceLists = [];
        $configFullCombinedPriceList = $this->configManager->get(Configuration::getConfigKeyToFullPriceList());
        if ($configFullCombinedPriceList) {
            $exceptPriceLists[] = $configFullCombinedPriceList;
        }

        return $exceptPriceLists;
    }

    private function getConfigPriceLists(): array
    {
        $configCombinedPriceList = $this->configManager->get(Configuration::getConfigKeyToPriceList());
        $exceptPriceLists = [];
        if ($configCombinedPriceList) {
            $exceptPriceLists[] = $configCombinedPriceList;
        }

        return $exceptPriceLists;
    }

    private function getAllConfigPriceLists(): array
    {
        return array_merge(
            $this->getConfigPriceLists(),
            $this->getConfigFullChainPriceLists()
        );
    }
}
