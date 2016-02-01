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
        $cacheKey = $this->getCacheKey($currentWebsite);
        if ($force || !$this->getCacheProvider()->contains($cacheKey)) {
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
            $this->getCacheProvider()->save($cacheKey, 1);
        }
    }

    /**
     * @param Website $website
     * @param boolean $force
     */
    protected function updatePriceListsOnCurrentLevel(Website $website, $force)
    {
        $priceListsToWebsite = $this->getPriceListToEntityRepository()
            ->findOneBy(['website' => $website]);
        if (!$priceListsToWebsite) {
            /** @var PriceListToWebsiteRepository $repo */
            $repo = $this->getCombinedPriceListToEntityRepository();
            $repo->delete($website);

            return;
        }
        $collection = $this->priceListCollectionProvider->getPriceListsByWebsite($website);
        $actualCombinedPriceList = $this->combinedPriceListProvider->getCombinedPriceList($collection, $force);

        $this->getCombinedPriceListRepository()
            ->updateCombinedPriceListConnection($actualCombinedPriceList, $website);
    }

    /**
     * @param Website|null $currentWebsite
     * @return string
     */
    protected function getCacheKey(Website $currentWebsite = null)
    {
        $key = 'config';
        if ($currentWebsite) {
            $key = sprintf('website_%d', $currentWebsite->getId());
        }

        return $key;
    }
}
