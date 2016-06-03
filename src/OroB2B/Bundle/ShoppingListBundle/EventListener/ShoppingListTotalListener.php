<?php

namespace OroB2B\Bundle\ShoppingListBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\Registry;

use OroB2B\Bundle\PricingBundle\Event\CombinedPriceList\AccountCPLUpdateEvent;
use OroB2B\Bundle\PricingBundle\Event\CombinedPriceList\AccountGroupCPLUpdateEvent;
use OroB2B\Bundle\PricingBundle\Event\CombinedPriceList\WebsiteCPLUpdateEvent;
use OroB2B\Bundle\PricingBundle\Event\CombinedPriceList\ConfigCPLUpdateEvent;
use OroB2B\Bundle\PricingBundle\Event\CombinedPriceList\CombinedPriceListsUpdateEvent;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;

class ShoppingListTotalListener
{
    const ACCOUNT_BATCH_SIZE = 500;
    
    /**
     * ShoppingListTotalListener constructor.
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param CombinedPriceListsUpdateEvent $event
     */
    public function onPriceListUpdate(CombinedPriceListsUpdateEvent $event)
    {
        $this->registry->getManagerForClass('OroB2BShoppingListBundle:ShoppingListTotal')
            ->getRepository('OroB2BShoppingListBundle:ShoppingListTotal')
            ->invalidateByCpl($event->getCombinedPriceListIds());
    }

    /**
     * @param AccountCPLUpdateEvent $event
     */
    public function onAccountPriceListUpdate(AccountCPLUpdateEvent $event)
    {
        $accountsData = $event->getAccountsData();
        $repo = $this->registry->getManagerForClass('OroB2BShoppingListBundle:ShoppingListTotal')
            ->getRepository('OroB2BShoppingListBundle:ShoppingListTotal');
        foreach ($accountsData as $data) {
            $repo->invalidateByAccounts($data['accounts'], $data['websiteId']);
        }
    }

    /**
     * @param AccountGroupCPLUpdateEvent $event
     */
    public function onAccountGroupPriceListUpdate(AccountGroupCPLUpdateEvent $event)
    {
        $accountsData = $event->getAccountGroupsData();
        $fallbackRepository = $this->registry->getManagerForClass('OroB2BPricingBundle:PriceListAccountFallback')
            ->getRepository('OroB2BPricingBundle:PriceListAccountFallback');
        $shoppingTotalsRepo = $this->registry->getManagerForClass('OroB2BShoppingListBundle:ShoppingListTotal')
            ->getRepository('OroB2BShoppingListBundle:ShoppingListTotal');
        foreach ($accountsData as $data) {
            $accounts = $fallbackRepository->getAccountIdentityByGroup($data['accountGroups'], $data['websiteId']);
            $i = 0;
            $ids = [];
            foreach ($accounts as $accountData) {
                $ids[] = $accountData['id'];
                $i++;
                if ($i % self::ACCOUNT_BATCH_SIZE === 0) {
                    $shoppingTotalsRepo->invalidateByAccounts($ids, $data['websiteId']);
                    $ids = [];
                }
            }
            if (!empty($ids)) {
                $shoppingTotalsRepo->invalidateByAccounts($ids, $data['websiteId']);
            }
        }
    }

    /**
     * @param WebsiteCPLUpdateEvent $event
     */
    public function onWebsitePriceListUpdate(WebsiteCPLUpdateEvent $event)
    {
        $websitesData = $event->getWebsiteIds();
        $fallbackRepository = $this->registry->getManagerForClass('OroB2BPricingBundle:PriceListAccountGroupFallback')
            ->getRepository('OroB2BPricingBundle:PriceListAccountGroupFallback');
        $shoppingTotalsRepo = $this->registry->getManagerForClass('OroB2BShoppingListBundle:ShoppingListTotal')
            ->getRepository('OroB2BShoppingListBundle:ShoppingListTotal');
        foreach ($websitesData as $websiteId) {
            $accounts = $fallbackRepository->getAccountIdentityByWebsite($websiteId);
            $i = 0;
            $ids = [];
            foreach ($accounts as $accountData) {
                $ids[] = $accountData['id'];
                $i++;
                if ($i % self::ACCOUNT_BATCH_SIZE === 0) {
                    $shoppingTotalsRepo->invalidateByAccounts($ids, $websiteId);
                    $ids = [];
                }
            }
            if (!empty($ids)) {
                $shoppingTotalsRepo->invalidateByAccounts($ids, $websiteId);
            }
        }
    }

    /**
     * @param ConfigCPLUpdateEvent $event
     */
    public function onConfigPriceListUpdate(ConfigCPLUpdateEvent $event)
    {
        $fallbackWebsiteRepository = $this->registry->getManagerForClass('OroB2BPricingBundle:PriceListWebsiteFallback')
            ->getRepository('OroB2BPricingBundle:PriceListWebsiteFallback');
        $fallbackRepository = $this->registry->getManagerForClass('OroB2BPricingBundle:PriceListAccountGroupFallback')
            ->getRepository('OroB2BPricingBundle:PriceListAccountGroupFallback');
        $shoppingTotalsRepo = $this->registry->getManagerForClass('OroB2BShoppingListBundle:ShoppingListTotal')
            ->getRepository('OroB2BShoppingListBundle:ShoppingListTotal');

        $websitesData = $fallbackWebsiteRepository->getWebsiteIdByDefaultFallback();
        foreach ($websitesData as $websiteData) {
            $accounts = $fallbackRepository->getAccountIdentityByWebsite($websiteData['id']);
            $i = 0;
            $ids = [];
            foreach ($accounts as $accountData) {
                $ids[] = $accountData['id'];
                $i++;
                if ($i % self::ACCOUNT_BATCH_SIZE === 0) {
                    $shoppingTotalsRepo->invalidateByAccounts($ids, $websiteData['id']);
                    $ids = [];
                }
            }
            if (!empty($ids)) {
                $shoppingTotalsRepo->invalidateByAccounts($ids, $websiteData['id']);
            }
        }
    }
}
