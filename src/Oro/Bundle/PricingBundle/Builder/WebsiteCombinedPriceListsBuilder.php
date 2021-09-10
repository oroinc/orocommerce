<?php

namespace Oro\Bundle\PricingBundle\Builder;

use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToWebsiteRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListWebsiteFallbackRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Updates or creates combined price lists for website scope
 *
 * Perform CPL build for website level,
 * call customer group CPL builder for groups with fallback to website
 *
 * @method PriceListToWebsiteRepository getPriceListToEntityRepository()
 *
 * @internal Allowed to be accessed only by CombinedPriceListsBuilderFacade
 */
class WebsiteCombinedPriceListsBuilder extends AbstractCombinedPriceListBuilder
{
    /**
     * @var CustomerGroupCombinedPriceListsBuilder
     */
    protected $customerGroupCombinedPriceListsBuilder;

    /**
     * @param CustomerGroupCombinedPriceListsBuilder $customerGroupCombinedPriceListsBuilder
     * @return $this
     */
    public function setCustomerGroupCombinedPriceListsBuilder($customerGroupCombinedPriceListsBuilder)
    {
        $this->customerGroupCombinedPriceListsBuilder = $customerGroupCombinedPriceListsBuilder;

        return $this;
    }

    /**
     * @param Website|null $currentWebsite
     * @param int|null $forceTimestamp
     */
    public function build(Website $currentWebsite = null, $forceTimestamp = null)
    {
        $websites = $this->getWebsitesForBuild($currentWebsite);
        foreach ($websites as $website) {
            if ($this->isBuiltForWebsite($website)) {
                continue;
            }
            $this->wrapInTransaction(function () use ($website, $forceTimestamp) {
                $this->updatePriceListsOnCurrentLevel($website, $forceTimestamp);
            });
            $this->setBuiltForWebsite($website);

            $this->customerGroupCombinedPriceListsBuilder->build($website, null, $forceTimestamp);
        }
    }

    /**
     * @param Website $website
     * @param int|null $forceTimestamp
     */
    protected function updatePriceListsOnCurrentLevel(Website $website, $forceTimestamp = null)
    {
        if (!$this->hasAssignedPriceLists($website)) {
            /** @var PriceListToWebsiteRepository $repo */
            $repo = $this->getCombinedPriceListToEntityRepository();
            $repo->delete($website);

            if ($this->hasFallbackOnNextLevel($website)) {
                //is this case price list would be fetched from next level, and there is no need to store the own
                return;
            }
        }
        $collection = $this->priceListCollectionProvider->getPriceListsByWebsite($website);
        $combinedPriceList = $this->combinedPriceListProvider->getCombinedPriceList($collection);
        $this->updateRelationsAndPrices($combinedPriceList, $website, null, $forceTimestamp);
    }

    /**
     * @param Website|null $website
     * @return bool
     */
    protected function isBuiltForWebsite(Website $website = null)
    {
        $websiteId = 0;
        if ($website) {
            $websiteId = $website->getId();
        }

        return !empty($this->builtList[$websiteId]);
    }

    protected function setBuiltForWebsite(Website $website = null)
    {
        $websiteId = 0;
        if ($website) {
            $websiteId = $website->getId();
        }

        $this->builtList[$websiteId] = true;
    }

    /**
     * @param Website $website
     * @return bool
     */
    protected function hasFallbackOnNextLevel(Website $website)
    {
        /** @var PriceListWebsiteFallbackRepository $repo */
        $repo = $this->getFallbackRepository();

        return $repo->hasFallbackOnNextLevel($website);
    }

    /**
     * @param Website $currentWebsite
     * @return iterable|Website[]
     */
    protected function getWebsitesForBuild(Website $currentWebsite = null)
    {
        if ($currentWebsite) {
            return [$currentWebsite];
        }

        return $this->getPriceListToEntityRepository()->getWebsiteIteratorWithDefaultFallback();
    }

    protected function hasAssignedPriceLists(Website $website): bool
    {
        /** @var PriceListToWebsiteRepository $repo */
        $repo = $this->getPriceListToEntityRepository();

        return $repo->hasAssignedPriceLists($website);
    }
}
