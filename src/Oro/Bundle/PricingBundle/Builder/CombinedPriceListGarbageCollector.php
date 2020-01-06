<?php

namespace Oro\Bundle\PricingBundle\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
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

    /**
     * @var string
     */
    protected $combinedPriceListClass;

    /**
     * @var CombinedPriceListRepository
     */
    protected $combinedPriceListsRepository;

    /**
     * @param ManagerRegistry $registry
     * @param ConfigManager $configManager
     * @param CombinedPriceListTriggerHandler $triggerHandler
     */
    public function __construct(
        ManagerRegistry $registry,
        ConfigManager $configManager,
        CombinedPriceListTriggerHandler $triggerHandler
    ) {
        $this->registry = $registry;
        $this->configManager = $configManager;
        $this->triggerHandler = $triggerHandler;
    }

    /**
     * @param string $combinedPriceListClass
     */
    public function setCombinedPriceListClass($combinedPriceListClass)
    {
        $this->combinedPriceListClass = $combinedPriceListClass;
        $this->combinedPriceListsRepository = null;
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
        $cplRepository = $this->getCombinedPriceListsRepository();

        $exceptPriceLists = $this->getAllConfigPriceLists();
        $priceListsForDelete = $cplRepository->getUnusedPriceListsIds($exceptPriceLists);
        $this->triggerHandler->startCollect();
        $this->triggerHandler->massProcess($priceListsForDelete);
        $cplRepository->deletePriceLists($priceListsForDelete);
        $this->triggerHandler->commit();
    }

    /**
     * @return array
     */
    private function getConfigFullChainPriceLists(): array
    {
        $exceptPriceLists = [];
        $configFullCombinedPriceList = $this->configManager->get(Configuration::getConfigKeyToFullPriceList());
        if ($configFullCombinedPriceList) {
            $exceptPriceLists[] = $configFullCombinedPriceList;
        }

        return $exceptPriceLists;
    }

    /**
     * @return array
     */
    private function getConfigPriceLists(): array
    {
        $configCombinedPriceList = $this->configManager->get(Configuration::getConfigKeyToPriceList());
        $exceptPriceLists = [];
        if ($configCombinedPriceList) {
            $exceptPriceLists[] = $configCombinedPriceList;
        }

        return $exceptPriceLists;
    }

    /**
     * @return array
     */
    private function getAllConfigPriceLists(): array
    {
        return array_merge(
            $this->getConfigPriceLists(),
            $this->getConfigFullChainPriceLists()
        );
    }

    /**
     * @return CombinedPriceListRepository
     */
    protected function getCombinedPriceListsRepository()
    {
        if (!$this->combinedPriceListsRepository) {
            $this->combinedPriceListsRepository = $this->registry->getManagerForClass($this->combinedPriceListClass)
                ->getRepository($this->combinedPriceListClass);
        }

        return $this->combinedPriceListsRepository;
    }

    /**
     * @return CombinedPriceListRepository
     */
    protected function getCombinedPriceListRelationRepository()
    {
        if (!$this->combinedPriceListsRepository) {
            $this->combinedPriceListsRepository = $this->registry->getManagerForClass($this->combinedPriceListClass)
                ->getRepository($this->combinedPriceListClass);
        }

        return $this->combinedPriceListsRepository;
    }
}
