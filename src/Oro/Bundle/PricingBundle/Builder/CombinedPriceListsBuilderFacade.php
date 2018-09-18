<?php

namespace Oro\Bundle\PricingBundle\Builder;

use Doctrine\Common\Collections\Collection;
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
use Oro\Bundle\PricingBundle\Model\DTO\PriceListRelationTrigger;
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

    /** @var array  */
    private $rebuiltCombinedPriceListsIds = [];

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param CustomerCombinedPriceListsBuilder $customerCombinedPriceListBuilder
     * @param CustomerGroupCombinedPriceListsBuilder $customerGroupCombinedPriceListBuilder
     * @param WebsiteCombinedPriceListsBuilder $websiteCombinedPriceListBuilder
     * @param CombinedPriceListsBuilder $combinedPriceListBuilder
     * @param EventDispatcherInterface $dispatcher
     * @param StrategyRegister $strategyRegister
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        CustomerCombinedPriceListsBuilder $customerCombinedPriceListBuilder,
        CustomerGroupCombinedPriceListsBuilder $customerGroupCombinedPriceListBuilder,
        WebsiteCombinedPriceListsBuilder $websiteCombinedPriceListBuilder,
        CombinedPriceListsBuilder $combinedPriceListBuilder,
        EventDispatcherInterface $dispatcher,
        StrategyRegister $strategyRegister
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->customerCombinedPriceListBuilder = $customerCombinedPriceListBuilder;
        $this->customerGroupCombinedPriceListBuilder = $customerGroupCombinedPriceListBuilder;
        $this->websiteCombinedPriceListBuilder = $websiteCombinedPriceListBuilder;
        $this->combinedPriceListBuilder = $combinedPriceListBuilder;
        $this->dispatcher = $dispatcher;
        $this->strategyRegister = $strategyRegister;
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
        $this->combinedPriceListBuilder->build($forceTimestamp);
        $this->websiteCombinedPriceListBuilder->build(null, $forceTimestamp);

        /** @var PriceListToCustomerGroupRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepositoryForClass(PriceListToCustomerGroup::class);
        foreach ($repository->getAllWebsiteIds() as $websiteId) {
            /** @var Website $website */
            $website = $this->getEntityById(Website::class, $websiteId);

            $this->customerGroupCombinedPriceListBuilder->build($website, null, $forceTimestamp);
        }

        /** @var PriceListToCustomerRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepositoryForClass(PriceListToCustomer::class);
        foreach ($repository->getAllCustomerWebsitePairs() as $pair) {
            $this->customerCombinedPriceListBuilder->build($pair->getWebsite(), $pair->getCustomer(), $forceTimestamp);
        }
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
    }

    /**
     * @param int[]|PriceList[]|Collection $priceLists
     * @param int|null $forceTimestamp
     */
    public function rebuildForPriceLists($priceLists, $forceTimestamp = null)
    {
        /** @var PriceListToWebsiteRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepositoryForClass(PriceListToWebsite::class);
        foreach ($repository->getIteratorByPriceLists($priceLists) as $ids) {
            /** @var Website $website */
            $website = $this->getEntityById(Website::class, $ids[PriceListRelationTrigger::WEBSITE]);

            $this->websiteCombinedPriceListBuilder->build($website, $forceTimestamp);
        }

        /** @var PriceListToCustomerGroupRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepositoryForClass(PriceListToCustomerGroup::class);
        foreach ($repository->getIteratorByPriceLists($priceLists) as $ids) {
            /** @var Website $website */
            $website = $this->getEntityById(Website::class, $ids[PriceListRelationTrigger::WEBSITE]);
            /** @var CustomerGroup $customerGroup */
            $customerGroup = $this->getEntityById(CustomerGroup::class, $ids[PriceListRelationTrigger::ACCOUNT_GROUP]);

            $this->customerGroupCombinedPriceListBuilder->build($website, $customerGroup, $forceTimestamp);
        }

        /** @var PriceListToCustomerRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepositoryForClass(PriceListToCustomer::class);
        foreach ($repository->getIteratorByPriceLists($priceLists) as $ids) {
            /** @var Website $website */
            $website = $this->getEntityById(Website::class, $ids[PriceListRelationTrigger::WEBSITE]);
            /** @var Customer $customer */
            $customer = $this->getEntityById(Customer::class, $ids[PriceListRelationTrigger::ACCOUNT]);

            $this->customerCombinedPriceListBuilder->build($website, $customer, $forceTimestamp);
        }
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

            $this->dispatcher->dispatch(CustomerCPLUpdateEvent::NAME, new CustomerCPLUpdateEvent($data));
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

            $this->dispatcher->dispatch(CustomerGroupCPLUpdateEvent::NAME, new CustomerGroupCPLUpdateEvent($data));
        }
    }

    private function dispatchWebsiteScopeEvent()
    {
        $websiteBuildList = $this->websiteCombinedPriceListBuilder->getBuiltList();
        if ($websiteBuildList) {
            $this->dispatcher->dispatch(
                WebsiteCPLUpdateEvent::NAME,
                new WebsiteCPLUpdateEvent(array_filter(array_keys($websiteBuildList)))
            );
        }
    }

    private function dispatchConfigScopeEvent()
    {
        if ($this->combinedPriceListBuilder->isBuilt()) {
            $this->dispatcher->dispatch(ConfigCPLUpdateEvent::NAME, new ConfigCPLUpdateEvent());
        }
    }

    private function dispatchCombinedPriceListsUpdateEvent()
    {
        if ($this->rebuiltCombinedPriceListsIds) {
            $this->dispatcher->dispatch(
                CombinedPriceListsUpdateEvent::NAME,
                new CombinedPriceListsUpdateEvent($this->rebuiltCombinedPriceListsIds)
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
}
