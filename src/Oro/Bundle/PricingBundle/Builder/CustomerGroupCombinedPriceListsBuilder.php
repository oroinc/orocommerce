<?php

namespace Oro\Bundle\PricingBundle\Builder;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListCustomerGroupFallbackRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerGroupRepository;
use Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Updates or creates combined price lists for customer group scope
 *
 * Perform CPL build for customer group level,
 * call customer CPL builder for customers with fallback to customer group,
 * call customer CPL builder for customers with fallback to customer group and with empty group when concrete customer
 * group not passed as $currentCustomerGroup parameter (build for all groups)
 *
 * @method PriceListToCustomerGroupRepository getPriceListToEntityRepository()
 *
 * @internal Allowed to be accessed only by CombinedPriceListsBuilderFacade
 */
class CustomerGroupCombinedPriceListsBuilder extends AbstractCombinedPriceListBuilder
{
    /**
     * @var CustomerCombinedPriceListsBuilder
     */
    protected $customerCombinedPriceListsBuilder;

    public function setCustomerCombinedPriceListsBuilder(CustomerCombinedPriceListsBuilder $builder)
    {
        $this->customerCombinedPriceListsBuilder = $builder;
    }

    /**
     * @param Website $website
     * @param CustomerGroup|null $currentCustomerGroup
     * @param int|null $forceTimestamp
     */
    public function build(Website $website, CustomerGroup $currentCustomerGroup = null, $forceTimestamp = null)
    {
        if (!$this->isBuiltForCustomerGroup($website, $currentCustomerGroup)) {
            $customerGroups = $this->getCustomerGroupsForBuild($website, $currentCustomerGroup);
            foreach ($customerGroups as $customerGroup) {
                $this->wrapInTransaction(function () use ($website, $customerGroup, $forceTimestamp) {
                    $this->updatePriceListsOnCurrentLevel($website, $customerGroup, $forceTimestamp);
                });

                $this->customerCombinedPriceListsBuilder
                    ->buildByCustomerGroup($website, $customerGroup, $forceTimestamp);
            }

            if (!$currentCustomerGroup) {
                $this->customerCombinedPriceListsBuilder->buildForCustomersWithoutGroupAndFallbackToGroup($website);
            }
            $this->setBuiltForCustomerGroup($website, $currentCustomerGroup);
        }
    }

    /**
     * @param Website $website
     * @param CustomerGroup $customerGroup
     * @param int|null $forceTimestamp
     */
    protected function updatePriceListsOnCurrentLevel(
        Website $website,
        CustomerGroup $customerGroup,
        $forceTimestamp = null
    ) {
        $hasFallbackOnNextLevel = $this->hasFallbackOnNextLevel($website, $customerGroup);
        if (!$this->hasAssignedPriceLists($website, $customerGroup)) {
            /** @var PriceListToCustomerGroupRepository $repo */
            $repo = $this->getCombinedPriceListToEntityRepository();
            $repo->delete($customerGroup, $website);

            if ($hasFallbackOnNextLevel) {
                //is this case price list would be fetched from next level, and there is no need to store the own
                return;
            }
        }
        $collection = $this->priceListCollectionProvider->getPriceListsByCustomerGroup($customerGroup, $website);
        $combinedPriceList = $this->combinedPriceListProvider->getCombinedPriceList($collection);

        if ($hasFallbackOnNextLevel
            && ($fallbackPriceLists = $this->getFallbackPriceLists($website))
            && !$this->priceListCollectionProvider->containMergeDisallowed($collection)
            && !$this->priceListCollectionProvider->containScheduled($collection)
        ) {
            $currentLevelPriceLists = array_splice($collection, 0, -\count($fallbackPriceLists));

            $this->updateRelationsAndPricesUsingFallback(
                $combinedPriceList,
                $website,
                $currentLevelPriceLists,
                $fallbackPriceLists,
                $customerGroup,
                $forceTimestamp
            );
        } else {
            $this->updateRelationsAndPrices($combinedPriceList, $website, $customerGroup, $forceTimestamp);
        }
    }

    /**
     * @param Website $website
     * @return array|PriceListSequenceMember[]
     */
    protected function getFallbackPriceLists(Website $website)
    {
        return $this->priceListCollectionProvider->getPriceListsByWebsite($website);
    }

    /**
     * @param Website $website
     * @param CustomerGroup|null $customerGroup
     * @return bool
     */
    protected function isBuiltForCustomerGroup(Website $website, CustomerGroup $customerGroup = null)
    {
        $customerGroupId = 0;
        if ($customerGroup) {
            $customerGroupId = $customerGroup->getId();
        }

        return !empty($this->builtList[$website->getId()][$customerGroupId]);
    }

    protected function setBuiltForCustomerGroup(Website $website, CustomerGroup $customerGroup = null)
    {
        $customerGroupId = 0;
        if ($customerGroup) {
            $customerGroupId = $customerGroup->getId();
        }

        $this->builtList[$website->getId()][$customerGroupId] = true;
    }

    /**
     * @param Website $website
     * @param CustomerGroup $customerGroup
     * @return bool
     */
    protected function hasFallbackOnNextLevel(Website $website, CustomerGroup $customerGroup)
    {
        /** @var PriceListCustomerGroupFallbackRepository $repo */
        $repo = $this->getFallbackRepository();

        return $repo->hasFallbackOnNextLevel($website, $customerGroup);
    }

    /**
     * @param Website $website
     * @param CustomerGroup|null $currentCustomerGroup
     * @return iterable|CustomerGroup[]
     */
    protected function getCustomerGroupsForBuild(
        Website $website,
        CustomerGroup $currentCustomerGroup = null
    ) {
        if ($currentCustomerGroup) {
            return [$currentCustomerGroup];
        }

        return $this->getPriceListToEntityRepository()->getCustomerGroupIteratorWithDefaultFallback($website);
    }

    protected function hasAssignedPriceLists(Website $website, CustomerGroup $customerGroup): bool
    {
        /** @var PriceListToCustomerGroupRepository $repo */
        $repo = $this->getPriceListToEntityRepository();

        return $repo->hasAssignedPriceLists($website, $customerGroup);
    }
}
