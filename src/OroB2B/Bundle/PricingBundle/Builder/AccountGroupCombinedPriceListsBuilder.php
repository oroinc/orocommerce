<?php

namespace OroB2B\Bundle\PricingBundle\Builder;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountGroupFallback;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountGroupRepository;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @method PriceListToAccountGroupRepository getPriceListToEntityRepository()
 */
class AccountGroupCombinedPriceListsBuilder extends AbstractCombinedPriceListBuilder
{
    /**
     * @var AccountCombinedPriceListsBuilder
     */
    protected $accountCombinedPriceListsBuilder;

    /**
     * @param AccountCombinedPriceListsBuilder $builder
     */
    public function setAccountCombinedPriceListsBuilder(AccountCombinedPriceListsBuilder $builder)
    {
        $this->accountCombinedPriceListsBuilder = $builder;
    }

    /**
     * @param Website $website
     * @param AccountGroup|null $currentAccountGroup
     * @param bool|false $force
     */
    public function build(Website $website, AccountGroup $currentAccountGroup = null, $force = false)
    {
        if (!$this->isBuiltForAccountGroup($website, $currentAccountGroup)) {
            $accountGroups = [$currentAccountGroup];
            if (!$currentAccountGroup) {
                $fallback = $force ? null : PriceListAccountGroupFallback::WEBSITE;
                $accountGroups = $this->getPriceListToEntityRepository()
                    ->getAccountGroupIteratorByDefaultFallback($website, $fallback);
            }

            foreach ($accountGroups as $accountGroup) {
                $this->updatePriceListsOnCurrentLevel($website, $accountGroup, $force);
                $this->accountCombinedPriceListsBuilder
                    ->buildByAccountGroup($website, $accountGroup, $force);
            }

            if ($currentAccountGroup) {
                $this->scheduleResolver->updateRelations();
                $this->garbageCollector->cleanCombinedPriceLists();
            }
            $this->setBuiltForAccountGroup($website, $currentAccountGroup);
        }
    }

    /**
     * @param Website $website
     * @param AccountGroup $accountGroup
     * @param bool $force
     */
    protected function updatePriceListsOnCurrentLevel(Website $website, AccountGroup $accountGroup, $force)
    {
        $priceListsToAccountGroup = $this->getPriceListToEntityRepository()
            ->findOneBy(['website' => $website, 'accountGroup' => $accountGroup]);
        if (!$priceListsToAccountGroup) {
            /** @var PriceListToAccountGroupRepository $repo */
            $repo = $this->getCombinedPriceListToEntityRepository();
            $repo->delete($accountGroup, $website);

            if ($this->hasFallbackOnNextLevel($website, $accountGroup)) {
                //is this case price list would be fetched from next level, and there is no need to store the own
                return;
            }
        }
        $collection = $this->priceListCollectionProvider->getPriceListsByAccountGroup($accountGroup, $website);
        $combinedPriceList = $this->combinedPriceListProvider->getCombinedPriceList($collection);
        $this->updateRelationsAndPrices($combinedPriceList, $website, $accountGroup, $force);
    }

    /**
     * @param Website $website
     * @param AccountGroup|null $accountGroup
     * @return bool
     */
    protected function isBuiltForAccountGroup(Website $website, AccountGroup $accountGroup = null)
    {
        $accountGroupId = 0;
        if ($accountGroup) {
            $accountGroupId = $accountGroup->getId();
        }
        return !empty($this->builtList[$website->getId()][$accountGroupId]);
    }

    /**
     * @param Website $website
     * @param AccountGroup|null $accountGroup
     */
    protected function setBuiltForAccountGroup(Website $website, AccountGroup $accountGroup = null)
    {
        $accountGroupId = 0;
        if ($accountGroup) {
            $accountGroupId = $accountGroup->getId();
        }

        $this->builtList[$website->getId()][$accountGroupId] = true;
    }

    /**
     * @param Website $website
     * @param AccountGroup $accountGroup
     * @return bool
     */
    public function hasFallbackOnNextLevel(Website $website, AccountGroup $accountGroup)
    {
        $fallback = $this->getFallbackRepository()->findOneBy(
            [
                'accountGroup' => $accountGroup,
                'website' => $website,
                'fallback' => PriceListAccountGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY
            ]
        );

        return $fallback === null;
    }
}
