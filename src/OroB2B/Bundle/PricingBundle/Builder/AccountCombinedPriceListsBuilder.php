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
     * @param bool|false $force
     */
    public function build(Website $website, Account $account, $force = false)
    {
        if (!$this->isBuiltForAccount($website, $account)) {
            $this->updatePriceListsOnCurrentLevel($website, $account, $force);
            $this->garbageCollector->cleanCombinedPriceLists();
            $this->setBuiltForAccount($website, $account);
        }
    }

    /**
     * @param Website $website
     * @param AccountGroup $accountGroup
     * @param bool|false $force
     */
    public function buildByAccountGroup(Website $website, AccountGroup $accountGroup, $force = false)
    {
        if (!$this->isBuiltForAccountGroup($website, $accountGroup)) {
            $fallback = $force ? null : PriceListAccountFallback::ACCOUNT_GROUP;
            $accounts = $this->getPriceListToEntityRepository()
                ->getAccountIteratorByDefaultFallback($accountGroup, $website, $fallback);

            foreach ($accounts as $account) {
                $this->updatePriceListsOnCurrentLevel($website, $account, $force);
            }
            $this->setBuiltForAccountGroup($website, $accountGroup);
        }
    }

    /**
     * @param Website $website
     * @param Account $account
     * @param bool $force
     */
    protected function updatePriceListsOnCurrentLevel(Website $website, Account $account, $force)
    {
        $priceListsToAccount = $this->getPriceListToEntityRepository()
            ->findOneBy(['website' => $website, 'account' => $account]);
        if (!$priceListsToAccount) {
            /** @var PriceListToAccountRepository $repo */
            $repo = $this->getCombinedPriceListToEntityRepository();
            $repo->delete($account, $website);

            if ($this->hasFallbackOnNextLevel($website, $account)) {
                //is this case price list would be fetched from next level, and there is no need to store the own
                return;
            }
        }
        $collection = $this->priceListCollectionProvider->getPriceListsByAccount($account, $website);
        $combinedPriceList = $this->combinedPriceListProvider->getCombinedPriceList($collection);
        $this->updateRelationsAndPrices($combinedPriceList, $website, $account, $force);
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

    /**
     * @param Website $website
     * @param Account $account
     * @return bool
     */
    public function hasFallbackOnNextLevel(Website $website, Account $account)
    {
        $fallback = $this->getFallbackRepository()->findOneBy(
            ['website' => $website, 'account' => $account, 'fallback' => PriceListAccountFallback::CURRENT_ACCOUNT_ONLY]
        );

        return $fallback === null;
    }
}
