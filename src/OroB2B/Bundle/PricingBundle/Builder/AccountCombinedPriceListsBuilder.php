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
     * @param int|null $behavior
     */
    public function build(Website $website, Account $account, $behavior = null)
    {
        if (!$this->isBuiltForAccount($website, $account)) {
            $this->updatePriceListsOnCurrentLevel($website, $account, $behavior);
            $this->garbageCollector->cleanCombinedPriceLists();
            $this->setBuiltForAccount($website, $account);
        }
    }

    /**
     * @param Website $website
     * @param AccountGroup $accountGroup
     * @param int|null $behavior
     * @param bool $force
     */
    public function buildByAccountGroup(Website $website, AccountGroup $accountGroup, $behavior = null, $force = false)
    {
        if (!$this->isBuiltForAccountGroup($website, $accountGroup)) {
            $fallback = $force ? null : PriceListAccountFallback::ACCOUNT_GROUP;
            $accounts = $this->getPriceListToEntityRepository()
                ->getAccountIteratorByDefaultFallback($accountGroup, $website, $fallback);

            foreach ($accounts as $account) {
                $this->updatePriceListsOnCurrentLevel($website, $account, $behavior);
            }
            $this->setBuiltForAccountGroup($website, $accountGroup);
        }
    }

    /**
     * @param Website $website
     * @param Account $account
     * @param int $behavior
     */
    protected function updatePriceListsOnCurrentLevel(Website $website, Account $account, $behavior)
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
        $combinedPriceList = $this->combinedPriceListProvider->getCombinedPriceList($collection, $behavior);
        //TODO: Same for ALL builders
        //TODO: get currently active CPL, switch to it, resolve prices for active CPL only, remove code below
        $this->getCombinedPriceListRepository()
            ->updateCombinedPriceListConnection($combinedPriceList, $website, $account);
    }

    /**
     * @param Website $website
     * @param Account $account
     * @return bool
     */
    protected function isBuiltForAccount(Website $website, Account $account)
    {
        return !empty($this->builtList['account'][$website->getId()][$account->getId()]);
    }
    /**
     * @param Website $website
     * @param Account $account
     */
    protected function setBuiltForAccount(Website $website, Account $account)
    {
        $this->builtList['account'][$website->getId()][$account->getId()] = true;
    }

    /**
     * @param Website $website
     * @param AccountGroup $accountGroup
     * @return bool
     */
    protected function isBuiltForAccountGroup(Website $website, AccountGroup $accountGroup)
    {
        return !empty($this->builtList['group'][$website->getId()][$accountGroup->getId()]);
    }
    /**
     * @param Website $website
     * @param AccountGroup $accountGroup
     */
    protected function setBuiltForAccountGroup(Website $website, AccountGroup $accountGroup)
    {
        $this->builtList['group'][$website->getId()][$accountGroup->getId()] = true;
    }
}
