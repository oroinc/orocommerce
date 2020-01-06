<?php

namespace Oro\Bundle\PricingBundle\Builder;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerGroupRepository;
use Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Updates or creates combined price lists for customer group scope
 *
 * @method PriceListToCustomerGroupRepository getPriceListToEntityRepository()
 */
class CustomerGroupCombinedPriceListsBuilder extends AbstractCombinedPriceListBuilder
{
    /**
     * @var CustomerCombinedPriceListsBuilder
     */
    protected $customerCombinedPriceListsBuilder;

    /**
     * @param CustomerCombinedPriceListsBuilder $builder
     */
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
            if (!$currentCustomerGroup) {
                $fallback = $forceTimestamp ? null : PriceListCustomerGroupFallback::WEBSITE;
                $customerGroups = $this->getPriceListToEntityRepository()
                    ->getCustomerGroupIteratorByDefaultFallback($website, $fallback);
            } else {
                $customerGroups = [$currentCustomerGroup];
            }

            foreach ($customerGroups as $customerGroup) {
                $this->wrapInTransaction(function () use ($website, $customerGroup, $forceTimestamp) {
                    $this->updatePriceListsOnCurrentLevel($website, $customerGroup, $forceTimestamp);
                });

                $this->customerCombinedPriceListsBuilder
                    ->buildByCustomerGroup($website, $customerGroup, $forceTimestamp);
            }

            if ($currentCustomerGroup) {
                $this->scheduleResolver->updateRelations();
                $this->garbageCollector->cleanCombinedPriceLists();
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
        $priceListsToCustomerGroup = $this->getPriceListToEntityRepository()
            ->findOneBy(['website' => $website, 'customerGroup' => $customerGroup]);
        $hasFallbackOnNextLevel = $this->hasFallbackOnNextLevel($website, $customerGroup);

        if (!$priceListsToCustomerGroup) {
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

    /**
     * @param Website $website
     * @param CustomerGroup|null $customerGroup
     */
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
    public function hasFallbackOnNextLevel(Website $website, CustomerGroup $customerGroup)
    {
        $fallback = $this->getFallbackRepository()->findOneBy(
            [
                'customerGroup' => $customerGroup,
                'website' => $website,
                'fallback' => PriceListCustomerGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY
            ]
        );

        return $fallback === null;
    }
}
