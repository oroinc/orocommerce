<?php

namespace Oro\Bundle\PricingBundle\Builder;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListCustomerFallbackRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerRepository;
use Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Updates or creates combined price lists for customer scope
 *
 * @method PriceListToCustomerRepository getPriceListToEntityRepository()
 *
 * @internal Allowed to be accessed only by CombinedPriceListsBuilderFacade
 */
class CustomerCombinedPriceListsBuilder extends AbstractCombinedPriceListBuilder
{
    /**
     * @var bool
     */
    private $customersWithDefaultFallbackBatchProcessing = false;

    /**
     * @var array
     */
    private $customerIdsWithPriceLists = [];

    /**
     * @param Website $website
     * @param Customer $customer
     * @param int|null $forceTimestamp
     */
    public function build(Website $website, Customer $customer, $forceTimestamp = null)
    {
        if ($this->isBuiltForCustomer($website, $customer)) {
            return;
        }

        $this->wrapInTransaction(function () use ($website, $customer, $forceTimestamp) {
            $this->updatePriceListsOnCurrentLevel($website, $customer, $forceTimestamp);
        });
    }

    /**
     * @param Website $website
     * @param CustomerGroup $customerGroup
     * @param int|null $forceTimestamp
     */
    public function buildByCustomerGroup(Website $website, CustomerGroup $customerGroup, $forceTimestamp = null)
    {
        if ($this->isBuiltForCustomerGroup($website, $customerGroup)) {
            return;
        }

        // Skip fallback checks as all customers already have fallback
        // because of getCustomerIteratorByDefaultFallback
        $this->customersWithDefaultFallbackBatchProcessing = true;
        $this->loadCustomerPriceListsInfoByWebsiteAndCustomerGroup($website, $customerGroup);
        $customers = $this->getPriceListToEntityRepository()
            ->getCustomerIteratorWithDefaultFallback($customerGroup, $website);

        foreach ($customers as $customer) {
            if ($this->isBuiltForCustomer($website, $customer)) {
                continue;
            }

            $this->wrapInTransaction(function () use ($website, $customer, $forceTimestamp) {
                $this->updatePriceListsOnCurrentLevel($website, $customer, $forceTimestamp);
            });
        }

        $this->customersWithDefaultFallbackBatchProcessing = false;
        $this->setBuiltForCustomerGroup($website, $customerGroup);
    }

    /**
     * @param Website $website
     * @param int|null $forceTimestamp
     */
    public function buildForCustomersWithoutGroupAndFallbackToGroup(Website $website, $forceTimestamp = null)
    {
        $customers = $this->getPriceListToEntityRepository()
            ->getAllCustomersWithEmptyGroupAndDefaultFallback($website);

        // Skip fallback checks as all customers already have fallback
        // because of getAllCustomersWithEmptyGroupAndDefaultFallback
        $this->customersWithDefaultFallbackBatchProcessing = true;
        $this->loadCustomerPriceListsInfoByWebsiteAndCustomerGroup($website);
        foreach ($customers as $customer) {
            if ($this->isBuiltForCustomer($website, $customer)) {
                continue;
            }

            $this->wrapInTransaction(function () use ($website, $customer, $forceTimestamp) {
                $this->updatePriceListsOnCurrentLevel($website, $customer, $forceTimestamp);
            });
            $this->setBuiltForCustomer($website, $customer);
        }

        $this->customersWithDefaultFallbackBatchProcessing = false;
    }

    /**
     * @param Website $website
     * @param Customer $customer
     * @param int|null $forceTimestamp
     */
    protected function updatePriceListsOnCurrentLevel(Website $website, Customer $customer, $forceTimestamp = null)
    {
        $hasFallbackOnNextLevel = $this->hasFallbackOnNextLevel($website, $customer);
        if (!$this->hasAssignedPriceLists($website, $customer)) {
            /** @var PriceListToCustomerRepository $repo */
            $repo = $this->getCombinedPriceListToEntityRepository();
            $repo->delete($customer, $website);

            if ($hasFallbackOnNextLevel) {
                //is this case price list would be fetched from next level, and there is no need to store the own
                return;
            }
        }
        $collection = $this->priceListCollectionProvider->getPriceListsByCustomer($customer, $website);
        $combinedPriceList = $this->combinedPriceListProvider->getCombinedPriceList($collection);

        if ($hasFallbackOnNextLevel
            && ($fallbackPriceLists = $this->getFallbackPriceLists($website, $customer))
            && !$this->priceListCollectionProvider->containMergeDisallowed($collection)
            && !$this->priceListCollectionProvider->containScheduled($collection)
        ) {
            $currentLevelPriceLists = array_splice($collection, 0, -\count($fallbackPriceLists));

            $this->updateRelationsAndPricesUsingFallback(
                $combinedPriceList,
                $website,
                $currentLevelPriceLists,
                $fallbackPriceLists,
                $customer,
                $forceTimestamp
            );
        } else {
            $this->updateRelationsAndPrices($combinedPriceList, $website, $customer, $forceTimestamp);
        }
        $this->setBuiltForCustomer($website, $customer);
    }

    /**
     * @param Website $website
     * @param Customer $customer
     * @return array|PriceListSequenceMember[]
     */
    protected function getFallbackPriceLists(Website $website, Customer $customer)
    {
        if ($customer->getGroup()) {
            return $this->priceListCollectionProvider
                ->getPriceListsByCustomerGroup($customer->getGroup(), $website);
        }

        return null;
    }

    /**
     * @param Website $website
     * @param Customer $customer
     * @return bool
     */
    protected function isBuiltForCustomer(Website $website, Customer $customer)
    {
        return !empty($this->builtList['customer'][$website->getId()][$customer->getId()]);
    }

    protected function setBuiltForCustomer(Website $website, Customer $customer)
    {
        $this->builtList['customer'][$website->getId()][$customer->getId()] = true;
    }

    /**
     * @param Website $website
     * @param CustomerGroup $customerGroup
     * @return bool
     */
    protected function isBuiltForCustomerGroup(Website $website, CustomerGroup $customerGroup)
    {
        return !empty($this->builtList['group'][$website->getId()][$customerGroup->getId()]);
    }

    protected function setBuiltForCustomerGroup(Website $website, CustomerGroup $customerGroup)
    {
        $this->builtList['group'][$website->getId()][$customerGroup->getId()] = true;
    }

    protected function hasFallbackOnNextLevel(Website $website, Customer $customer): bool
    {
        if ($this->customersWithDefaultFallbackBatchProcessing) {
            return true;
        }

        /** @var PriceListCustomerFallbackRepository $repo */
        $repo = $this->getFallbackRepository();

        return $repo->hasFallbackOnNextLevel($website, $customer);
    }

    protected function hasAssignedPriceLists(Website $website, Customer $customer): bool
    {
        if ($this->customersWithDefaultFallbackBatchProcessing) {
            return !empty($this->customerIdsWithPriceLists[$website->getId()][$customer->getId()]);
        }

        /** @var PriceListToCustomerRepository $repo */
        $repo = $this->getPriceListToEntityRepository();

        return $repo->hasAssignedPriceLists($website, $customer);
    }

    /**
     * Mass load information about customers with assigned price lists to avoid n+1 query in loop
     */
    protected function loadCustomerPriceListsInfoByWebsiteAndCustomerGroup(
        Website $website,
        CustomerGroup $customerGroup = null
    ) {
        /** @var PriceListToCustomerRepository $repo */
        $repo = $this->getPriceListToEntityRepository();

        $this->customerIdsWithPriceLists[$website->getId()] = $repo
            ->getCustomersWithAssignedPriceLists($website, $customerGroup);
    }

    /**
     * {@inheritDoc}
     */
    public function resetCache()
    {
        $this->customerIdsWithPriceLists = [];

        return parent::resetCache();
    }
}
