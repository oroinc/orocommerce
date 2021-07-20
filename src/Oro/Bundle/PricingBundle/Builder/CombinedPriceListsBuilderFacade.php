<?php

namespace Oro\Bundle\PricingBundle\Builder;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\PriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerGroupRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToWebsiteRepository;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CombinedPriceListsUpdateEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\ConfigCPLUpdateEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CustomerCPLUpdateEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CustomerGroupCPLUpdateEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\WebsiteCPLUpdateEvent;
use Oro\Bundle\PricingBundle\PricingStrategy\StrategyRegister;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * This class provides a clean interface for rebuilding combined price lists
 * and dispatches required events when CPLs are updated
 */
class CombinedPriceListsBuilderFacade
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var CustomerCombinedPriceListsBuilder */
    private $customerCombinedPriceListBuilder;

    /** @var CustomerGroupCombinedPriceListsBuilder */
    private $customerGroupCombinedPriceListBuilder;

    /** @var WebsiteCombinedPriceListsBuilder */
    private $websiteCombinedPriceListBuilder;

    /** @var CombinedPriceListsBuilder */
    private $combinedPriceListBuilder;

    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var StrategyRegister */
    private $strategyRegister;

    /** @var CombinedPriceListGarbageCollector */
    private $garbageCollector;

    /** @var ConfigManager */
    private $configManager;

    /** @var array  */
    private $rebuiltCombinedPriceListsIds = [];

    public function __construct(
        DoctrineHelper $doctrineHelper,
        CustomerCombinedPriceListsBuilder $customerCombinedPriceListBuilder,
        CustomerGroupCombinedPriceListsBuilder $customerGroupCombinedPriceListBuilder,
        WebsiteCombinedPriceListsBuilder $websiteCombinedPriceListBuilder,
        CombinedPriceListsBuilder $combinedPriceListBuilder,
        EventDispatcherInterface $dispatcher,
        StrategyRegister $strategyRegister,
        CombinedPriceListGarbageCollector $garbageCollector,
        ConfigManager $configManager
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->customerCombinedPriceListBuilder = $customerCombinedPriceListBuilder;
        $this->customerGroupCombinedPriceListBuilder = $customerGroupCombinedPriceListBuilder;
        $this->websiteCombinedPriceListBuilder = $websiteCombinedPriceListBuilder;
        $this->combinedPriceListBuilder = $combinedPriceListBuilder;
        $this->dispatcher = $dispatcher;
        $this->strategyRegister = $strategyRegister;
        $this->garbageCollector = $garbageCollector;
        $this->configManager = $configManager;
    }

    /**
     * @param iterable|CombinedPriceList[] $combinedPriceLists
     * @param array|Product[] $products
     * @param int|null $startTimestamp
     */
    public function rebuild($combinedPriceLists, array $products = [], $startTimestamp = null)
    {
        $strategy = $this->strategyRegister->getCurrentStrategy();
        foreach ($combinedPriceLists as $combinedPriceList) {
            $strategy->combinePrices($combinedPriceList, $products, $startTimestamp);

            $this->rebuiltCombinedPriceListsIds[] = $combinedPriceList->getId();
        }
    }

    /**
     * @param int|null $forceTimestamp
     */
    public function rebuildAll($forceTimestamp = null)
    {
        // Execute builders config -> website (<- config) -> customer group (<- website) -> customers (<- group)
        $this->combinedPriceListBuilder->build($forceTimestamp);

        // Rebuild for entities with configured self-fallback (Current level only)
        $this->rebuildForWebsitesWithSelfFallback($forceTimestamp);
        $this->rebuildForCustomerGroupsWithSelfFallback($forceTimestamp);
        $this->rebuildForCustomersWithSelfFallback($forceTimestamp);

        $this->garbageCollector->cleanCombinedPriceLists();
    }

    /**
     * @param Website[] $websites
     * @param int|null $forceTimestamp
     */
    public function rebuildForWebsites($websites, $forceTimestamp = null)
    {
        foreach ($websites as $website) {
            $this->websiteCombinedPriceListBuilder->build($website, $forceTimestamp);
        }

        $this->garbageCollector->cleanCombinedPriceLists();
    }

    /**
     * @param CustomerGroup[] $customerGroups
     * @param Website $website
     * @param int|null $forceTimestamp
     */
    public function rebuildForCustomerGroups($customerGroups, Website $website, $forceTimestamp = null)
    {
        foreach ($customerGroups as $customerGroup) {
            $this->customerGroupCombinedPriceListBuilder->build($website, $customerGroup, $forceTimestamp);
        }

        $this->garbageCollector->cleanCombinedPriceLists();
    }

    /**
     * @param int[]|Customer[]|Collection $customers
     * @param Website $website
     * @param int|null $forceTimestamp
     */
    public function rebuildForCustomers($customers, Website $website, $forceTimestamp = null)
    {
        foreach ($customers as $customer) {
            $this->customerCombinedPriceListBuilder->build($website, $customer, $forceTimestamp);
        }

        $this->garbageCollector->cleanCombinedPriceLists();
    }

    /**
     * @param int[]|PriceList[]|Collection $priceLists
     * @param int|null $forceTimestamp
     */
    public function rebuildForPriceLists($priceLists, $forceTimestamp = null)
    {
        $this->rebuildByConfigPriceLists($priceLists, $forceTimestamp);
        $this->rebuildByWebsitePriceLists($priceLists, $forceTimestamp);
        $this->rebuildByCustomerGroupPriceLists($priceLists, $forceTimestamp);
        $this->rebuildByCustomerPriceLists($priceLists, $forceTimestamp);

        $this->garbageCollector->cleanCombinedPriceLists();
    }

    public function dispatchEvents()
    {
        $this->dispatchCustomerScopeEvent();
        $this->dispatchCustomerGroupScopeEvent();
        $this->dispatchWebsiteScopeEvent();
        $this->dispatchConfigScopeEvent();
        $this->dispatchCombinedPriceListsUpdateEvent();

        $this->resetCache();
    }

    private function resetCache()
    {
        $this->combinedPriceListBuilder->resetCache();
        $this->websiteCombinedPriceListBuilder->resetCache();
        $this->customerGroupCombinedPriceListBuilder->resetCache();
        $this->customerCombinedPriceListBuilder->resetCache();
        $this->rebuiltCombinedPriceListsIds = [];
    }

    private function dispatchCustomerScopeEvent()
    {
        $customerBuildList = $this->customerCombinedPriceListBuilder->getBuiltList();
        $customerScope = $customerBuildList['customer'] ?? [];
        if ($customerScope) {
            $data = [];
            foreach ($customerScope as $websiteId => $customers) {
                $data[] = ['websiteId' => $websiteId, 'customers' => array_filter(array_keys($customers))];
            }

            $this->dispatcher->dispatch(new CustomerCPLUpdateEvent($data), CustomerCPLUpdateEvent::NAME);
        }
    }

    private function dispatchCustomerGroupScopeEvent()
    {
        $customerGroupBuildList = $this->customerGroupCombinedPriceListBuilder->getBuiltList();
        if ($customerGroupBuildList) {
            $data = [];
            foreach ($customerGroupBuildList as $websiteId => $customerGroups) {
                $data[] = ['websiteId' => $websiteId, 'customerGroups' => array_filter(array_keys($customerGroups))];
            }

            $this->dispatcher->dispatch(new CustomerGroupCPLUpdateEvent($data), CustomerGroupCPLUpdateEvent::NAME);
        }
    }

    private function dispatchWebsiteScopeEvent()
    {
        $websiteBuildList = $this->websiteCombinedPriceListBuilder->getBuiltList();
        if ($websiteBuildList) {
            $this->dispatcher->dispatch(
                new WebsiteCPLUpdateEvent(array_filter(array_keys($websiteBuildList))),
                WebsiteCPLUpdateEvent::NAME
            );
        }
    }

    private function dispatchConfigScopeEvent()
    {
        if ($this->combinedPriceListBuilder->isBuilt()) {
            $this->dispatcher->dispatch(new ConfigCPLUpdateEvent(), ConfigCPLUpdateEvent::NAME);
        }
    }

    private function dispatchCombinedPriceListsUpdateEvent()
    {
        if ($this->rebuiltCombinedPriceListsIds) {
            $this->dispatcher->dispatch(
                new CombinedPriceListsUpdateEvent($this->rebuiltCombinedPriceListsIds),
                CombinedPriceListsUpdateEvent::NAME
            );
        }
    }

    /**
     * @param string $className
     * @param int $id
     * @return object
     */
    private function getEntityById($className, $id)
    {
        return $this->doctrineHelper->getEntityReference($className, $id);
    }

    /**
     * @param int|null $forceTimestamp
     */
    private function rebuildForWebsitesWithSelfFallback($forceTimestamp = null)
    {
        /** @var PriceListToWebsiteRepository $plToWebsiteRepo */
        $plToWebsiteRepo = $this->doctrineHelper->getEntityRepositoryForClass(PriceListToWebsite::class);
        foreach ($plToWebsiteRepo->getWebsiteIteratorWithSelfFallback() as $website) {
            $this->websiteCombinedPriceListBuilder->build($website, $forceTimestamp);
        }
    }

    /**
     * @param int|null $forceTimestamp
     */
    private function rebuildForCustomerGroupsWithSelfFallback($forceTimestamp = null): void
    {
        /** @var PriceListToCustomerGroupRepository $plToCustomerGroupRepo */
        $plToCustomerGroupRepo = $this->doctrineHelper->getEntityRepositoryForClass(PriceListToCustomerGroup::class);
        foreach ($plToCustomerGroupRepo->getAllWebsiteIds() as $websiteId) {
            /** @var Website $website */
            $website = $this->getEntityById(Website::class, $websiteId);

            foreach ($plToCustomerGroupRepo->getCustomerGroupIteratorWithSelfFallback($website) as $customerGroup) {
                $this->customerGroupCombinedPriceListBuilder->build($website, $customerGroup, $forceTimestamp);
            }
        }
    }

    /**
     * @param int|null $forceTimestamp
     */
    private function rebuildForCustomersWithSelfFallback($forceTimestamp = null): void
    {
        /** @var PriceListToCustomerRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepositoryForClass(PriceListToCustomer::class);
        foreach ($repository->getAllCustomerWebsitePairsWithSelfFallback() as $pair) {
            $this->customerCombinedPriceListBuilder->build($pair->getWebsite(), $pair->getCustomer(), $forceTimestamp);
        }
    }

    /**
     * @param int[]|PriceList[]|Collection $priceLists
     * @return bool
     */
    private function hasPriceListsInConfig($priceLists): bool
    {
        $priceListIds = array_map(function (PriceList $priceList) {
            return $priceList->getId();
        }, $priceLists);
        $configPriceListRelations = $this->configManager->get('oro_pricing.default_price_lists');
        foreach ($configPriceListRelations as $relation) {
            if (\in_array((int)$relation['priceList'], $priceListIds, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param int[]|PriceList[]|Collection $priceLists
     * @param int|null $forceTimestamp
     */
    private function rebuildByConfigPriceLists($priceLists, $forceTimestamp): void
    {
        if ($this->hasPriceListsInConfig($priceLists)) {
            $this->combinedPriceListBuilder->build($forceTimestamp);
        }
    }

    /**
     * @param int[]|PriceList[]|Collection $priceLists
     * @param int|null $forceTimestamp
     */
    private function rebuildByWebsitePriceLists($priceLists, $forceTimestamp): void
    {
        /** @var PriceListToWebsiteRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepositoryForClass(PriceListToWebsite::class);
        foreach ($repository->getIteratorByPriceLists($priceLists) as $ids) {
            /** @var Website $website */
            $website = $this->getEntityById(Website::class, $ids['website']);

            $this->websiteCombinedPriceListBuilder->build($website, $forceTimestamp);
        }
    }

    /**
     * @param int[]|PriceList[]|Collection $priceLists
     * @param int|null $forceTimestamp
     */
    private function rebuildByCustomerGroupPriceLists($priceLists, $forceTimestamp): void
    {
        /** @var PriceListToCustomerGroupRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepositoryForClass(PriceListToCustomerGroup::class);
        foreach ($repository->getIteratorByPriceLists($priceLists) as $ids) {
            /** @var Website $website */
            $website = $this->getEntityById(Website::class, $ids['website']);
            /** @var CustomerGroup $customerGroup */
            $customerGroup = $this->getEntityById(CustomerGroup::class, $ids['customerGroup']);

            $this->customerGroupCombinedPriceListBuilder->build($website, $customerGroup, $forceTimestamp);
        }
    }

    /**
     * @param int[]|PriceList[]|Collection $priceLists
     * @param int|null $forceTimestamp
     */
    private function rebuildByCustomerPriceLists($priceLists, $forceTimestamp): void
    {
        /** @var PriceListToCustomerRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepositoryForClass(PriceListToCustomer::class);
        foreach ($repository->getIteratorByPriceLists($priceLists) as $ids) {
            /** @var Website $website */
            $website = $this->getEntityById(Website::class, $ids['website']);
            /** @var Customer $customer */
            $customer = $this->getEntityById(Customer::class, $ids['customer']);

            $this->customerCombinedPriceListBuilder->build($website, $customer, $forceTimestamp);
        }
    }
}
