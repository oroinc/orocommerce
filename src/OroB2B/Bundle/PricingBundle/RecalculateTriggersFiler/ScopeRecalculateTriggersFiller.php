<?php

namespace OroB2B\Bundle\PricingBundle\RecalculateTriggersFiler;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityBundle\ORM\Registry;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceListChangeTrigger;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class ScopeRecalculateTriggersFiller
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param Website[] $websiteIds
     * @param AccountGroup[] $accountGroupIds
     * @param Account[] $accountIds
     * @param $force
     */
    public function fillTriggersForRecalculate($websiteIds, $accountGroupIds, $accountIds, $force)
    {
        $websites = $this->getRecalculatedWebsites($websiteIds);
        $accountGroups = $this->getRecalculatedAccountGroups($accountGroupIds);
        $accounts = $this->getRecalculatedAccounts($accountIds);


        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass('OroB2BPricingBundle:PriceListChangeTrigger');

        if ($force) {
            if (empty($websites) && empty($accountGroups) && empty($accounts)) {
                $this->clearAllExistingTriggers();
                $priceListChangeTrigger = new PriceListChangeTrigger();

                $em->persist($priceListChangeTrigger);
            }
        } else {
            if ($accountGroups && $accounts) {
                $this->clearExistingScopesPriceListChangeTriggers($websites, $accountGroups, []);
                $this->clearExistingScopesPriceListChangeTriggers($websites, [], $accounts);
            } else {
                $this->clearExistingScopesPriceListChangeTriggers($websites, $accountGroups, $accounts);
            }
            $this->preparePriceListChangeTriggersForScopes($em, $websites, $accountGroups, $accounts);
        }

        $em->flush();
    }

    protected function clearAllExistingTriggers()
    {
        $this->registry->getManagerForClass('OroB2BPricingBundle:PriceListChangeTrigger')
            ->getRepository('OroB2BPricingBundle:PriceListChangeTrigger')
            ->deleteAll();

        $this->registry->getManagerForClass('OroB2BPricingBundle:ProductPriceChangeTrigger')
            ->getRepository('OroB2BPricingBundle:ProductPriceChangeTrigger')
            ->deleteAll();
    }

    /**
     * @param Website[] $websites
     * @param AccountGroup[] $accountGroups
     * @param Account[] $accounts
     */
    protected function clearExistingScopesPriceListChangeTriggers($websites = [], $accountGroups = [], $accounts = [])
    {
        $qb = $this->registry->getManagerForClass('OroB2BPricingBundle:PriceListChangeTrigger')
            ->getRepository('OroB2BPricingBundle:PriceListChangeTrigger')
            ->createQueryBuilder('priceListChangeTrigger');

        $qb->delete('OroB2BPricingBundle:PriceListChangeTrigger', 'priceListChangeTrigger');

        if ($websites) {
            $qb->andWhere($qb->expr()->in('priceListChangeTrigger.website', $websites));
        }
        if ($accountGroups) {
            $qb->andWhere($qb->expr()->in('priceListChangeTrigger.website', $accountGroups));
        }
        if ($accounts) {
            $qb->andWhere($qb->expr()->in('priceListChangeTrigger.website', $accounts));
        }

        $qb->getQuery()->execute();
    }

    /**
     * @param EntityManager $em
     * @param Website[] $websites
     * @param AccountGroup[] $accountGroups
     * @param Account[] $accounts
     */
    protected function preparePriceListChangeTriggersForScopes(EntityManager $em, $websites, $accountGroups, $accounts)
    {
        if (empty($websites) && (!empty($accountGroups) || !empty($accounts))) {
            $websites = $this->registry->getRepository('OroB2BWebsiteBundle:Website')->findAll();
        }

        foreach ($websites as $website) {
            if (empty($accountGroups) && empty($accounts)) {
                $this->persistWebsiteScopeTrigger($em, $website);
            }

            if ($accountGroups) {
                $this->persistAccountGroupScopeTriggers($em, $website, $accountGroups);
            }

            if ($accounts) {
                $this->persistAccountScopeTriggers($em, $website, $accounts);
            }
        }
    }

    /**
     * @param EntityManager $em
     * @param Website $website
     */
    protected function persistWebsiteScopeTrigger(EntityManager $em, Website $website)
    {
        $priceListChangeTriggerForWebsite = new PriceListChangeTrigger();
        $priceListChangeTriggerForWebsite->setWebsite($website);

        $em->persist($priceListChangeTriggerForWebsite);
    }

    /**
     * @param EntityManager $em
     * @param Website $website
     * @param AccountGroup[] $accountGroups
     */
    protected function persistAccountGroupScopeTriggers(EntityManager $em, Website $website, array $accountGroups)
    {
        foreach ($accountGroups as $accountGroup) {
            $priceListChangeTriggerForAccountGroup = new PriceListChangeTrigger();
            $priceListChangeTriggerForAccountGroup->setWebsite($website);
            $priceListChangeTriggerForAccountGroup->setAccountGroup($accountGroup);

            $em->persist($priceListChangeTriggerForAccountGroup);
        }
    }

    /**
     * @param EntityManager $em
     * @param Website $website
     * @param Account[] $accounts
     */
    protected function persistAccountScopeTriggers(EntityManager $em, Website $website, array $accounts)
    {
        foreach ($accounts as $account) {
            $priceListChangeTriggerForAccount = new PriceListChangeTrigger();
            $priceListChangeTriggerForAccount->setWebsite($website);
            $priceListChangeTriggerForAccount->setAccount($account);

            $em->persist($priceListChangeTriggerForAccount);
        }
    }

    /**
     * @param array $websiteIds
     * @return array|Website[]
     */
    protected function getRecalculatedWebsites(array $websiteIds)
    {
        $websites = [];

        if (!empty($websiteIds)) {
            $websites = $this->registry
                ->getRepository('OroB2BWebsiteBundle:Website')
                ->findBy(['id' => $websiteIds]);
        }

        return $websites;
    }

    /**
     * @param array $accountGroupIds
     * @return array|AccountGroup[]
     */
    protected function getRecalculatedAccountGroups(array $accountGroupIds)
    {
        $accountGroups = [];

        if (!empty($accountGroupIds)) {
            $accountGroups = $this->registry
                ->getRepository('OroB2BAccountBundle:AccountGroup')
                ->findBy(['id' => $accountGroupIds]);
        }

        return $accountGroups;
    }

    /**
     * @param array $accountIds
     * @return array|Account[]
     */
    protected function getRecalculatedAccounts(array $accountIds)
    {
        $accounts = [];

        if (!empty($accountIds)) {
            $accounts = $this->registry
                ->getRepository('OroB2BAccountBundle:Account')
                ->findBy(['id' => $accountIds]);
        }

        return $accounts;
    }
}
