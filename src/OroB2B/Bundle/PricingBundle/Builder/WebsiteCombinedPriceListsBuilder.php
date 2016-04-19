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
     * @param int|null $behavior
     */
    public function build(Website $currentWebsite = null, $behavior = null)
    {
        if (!$this->isBuiltForWebsite($currentWebsite)) {
            $websites = [$currentWebsite];
            if (!$currentWebsite) {
                $websites = $this->getPriceListToEntityRepository()
                    ->getWebsiteIteratorByDefaultFallback(PriceListWebsiteFallback::CONFIG);
            }

            foreach ($websites as $website) {
                $this->updatePriceListsOnCurrentLevel($website, $behavior);
                $this->accountGroupCombinedPriceListsBuilder->build($website, null, $behavior);
            }

            if ($currentWebsite) {
                $this->garbageCollector->cleanCombinedPriceLists();
            }
            $this->setBuiltForWebsite($currentWebsite);
        }
    }

    /**
     * @param Website $website
     * @param int|null $behavior
     */
    protected function updatePriceListsOnCurrentLevel(Website $website, $behavior)
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
        $actualCombinedPriceList = $this->combinedPriceListProvider->getCombinedPriceList($collection, $behavior);

        $this->getCombinedPriceListRepository()
            ->updateCombinedPriceListConnection($actualCombinedPriceList, $website);
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

    /**
     * @param Website|null $website
     */
    protected function setBuiltForWebsite(Website $website = null)
    {
        $websiteId = 0;
        if ($website) {
            $websiteId  = $website->getId();
        }

        $this->builtList[$websiteId] = true;
    }
}
