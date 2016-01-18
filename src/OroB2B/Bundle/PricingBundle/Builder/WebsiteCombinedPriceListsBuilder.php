<?php

namespace OroB2B\Bundle\PricingBundle\Builder;

use OroB2B\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToWebsiteRepository;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @method PriceListToWebsiteRepository getPriceListToEntityRepository()
 */
class WebsiteCombinedPriceListsBuilder extends AbstractCombinedPriceListBuilder
{
    /**
     * @var AccountGroupCombinedPriceListsBuilder
     */
    protected $accountGroupCombinedPriceListsBuilder;

    /**
     * @param AccountGroupCombinedPriceListsBuilder $accountGroupCombinedPriceListsBuilder
     * @return $this
     */
    public function setAccountGroupCombinedPriceListsBuilder($accountGroupCombinedPriceListsBuilder)
    {
        $this->accountGroupCombinedPriceListsBuilder = $accountGroupCombinedPriceListsBuilder;

        return $this;
    }

    /**
     * @param Website|null $currentWebsite
     * @param boolean|false $force
     */
    public function build(Website $currentWebsite = null, $force = false)
    {
        $websites = [$currentWebsite];
        if (!$currentWebsite) {
            $websites = $this->getPriceListToEntityRepository()
                ->getWebsiteIteratorByFallback(PriceListWebsiteFallback::CONFIG);
        }

        foreach ($websites as $website) {
            $this->updatePriceListsOnCurrentLevel($website, $force);
            $this->accountGroupCombinedPriceListsBuilder->build($website, null, $force);
        }

        if ($currentWebsite) {
            $this->garbageCollector->cleanCombinedPriceLists();
        }
    }

    /**
     * @param Website $website
     * @param boolean $force
     */
    protected function updatePriceListsOnCurrentLevel(Website $website, $force)
    {
        $collection = $this->priceListCollectionProvider->getPriceListsByWebsite($website);
        $actualCombinedPriceList = $this->combinedPriceListProvider->getCombinedPriceList($collection, $force);

        $this->getCombinedPriceListRepository()
            ->updateCombinedPriceListConnection($actualCombinedPriceList, $website);
    }
}
