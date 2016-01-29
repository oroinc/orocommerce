<?php

namespace OroB2B\Bundle\PricingBundle\Builder;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountFallback;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountRepository;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @method PriceListToAccountRepository getPriceListToEntityRepository()
 */
class AccountCombinedPriceListsBuilder extends AbstractCombinedPriceListBuilder
{
    /**
     * @param Website $website
     * @param Account $account
     * @param boolean|false $force
     */
    public function build(Website $website, Account $account, $force = false)
    {
        $cacheKey = $this->getCacheKey($website, $account);
        if ($force || !$this->getCacheProvider()->contains($cacheKey)) {
            $this->updatePriceListsOnCurrentLevel($website, $account, $force);
            $this->garbageCollector->cleanCombinedPriceLists();
            $this->getCacheProvider()->save($cacheKey, 1);
        }
    }

    /**
     * @param Website $website
     * @param AccountGroup $accountGroup
     * @param boolean|false $force
     */
    public function buildByAccountGroup(Website $website, AccountGroup $accountGroup, $force = false)
    {
        if ($force || !$this->getCacheProvider()->contains($this->getGroupCacheKey($website, $accountGroup))) {
            $accounts = $this->getPriceListToEntityRepository()
                ->getAccountIteratorByFallback($accountGroup, $website, PriceListAccountFallback::ACCOUNT_GROUP);

            foreach ($accounts as $account) {
                $this->updatePriceListsOnCurrentLevel($website, $account, $force);
            }
        }
    }

    /**
     * @param Website $website
     * @param Account $account
     * @param boolean $force
     */
    protected function updatePriceListsOnCurrentLevel(Website $website, Account $account, $force)
    {
        $priceListsToAccount = $this->getPriceListToEntityRepository()
            ->findOneBy(['website' => $website, 'account' => $account]);
        if (!$priceListsToAccount) {
            /** @var PriceListToAccountRepository $repo */
            $repo = $this->getCombinedPriceListToEntityRepository();
            $repo->delete($account, $website);

            return;
        }
        $collection = $this->priceListCollectionProvider->getPriceListsByAccount($account, $website);
        $combinedPriceList = $this->combinedPriceListProvider->getCombinedPriceList($collection, $force);

        $this->getCombinedPriceListRepository()
            ->updateCombinedPriceListConnection($combinedPriceList, $website, $account);
    }

    /**
     * @param Website $website
     * @param Account $account
     * @return string
     */
    protected function getCacheKey(Website $website, Account $account)
    {
        return sprintf('website_%d_account_%d', $website->getId(), $account->getId());
    }

    /**
     * @param Website $website
     * @param AccountGroup $accountGroup
     * @return string
     */
    protected function getGroupCacheKey(Website $website, AccountGroup $accountGroup)
    {
        return sprintf('website_%d_group_%d', $website->getId(), $accountGroup->getId());
    }
}
