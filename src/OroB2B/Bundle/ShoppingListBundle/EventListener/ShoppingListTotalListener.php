<?php

namespace OroB2B\Bundle\ShoppingListBundle\EventListener;

use Symfony\Bridge\Doctrine\RegistryInterface;

use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListAccountFallbackRepository;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListAccountGroupFallbackRepository;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListWebsiteFallbackRepository;
use OroB2B\Bundle\PricingBundle\Event\CombinedPriceList\AccountCPLUpdateEvent;
use OroB2B\Bundle\PricingBundle\Event\CombinedPriceList\AccountGroupCPLUpdateEvent;
use OroB2B\Bundle\PricingBundle\Event\CombinedPriceList\WebsiteCPLUpdateEvent;
use OroB2B\Bundle\PricingBundle\Event\CombinedPriceList\ConfigCPLUpdateEvent;
use OroB2B\Bundle\PricingBundle\Event\CombinedPriceList\CombinedPriceListsUpdateEvent;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListTotalRepository;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;

class ShoppingListTotalListener
{
    const ACCOUNT_BATCH_SIZE = 500;

    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
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
        /** @var ShoppingListTotalRepository $repository */
        $repository = $this->registry->getManagerForClass('OroB2BShoppingListBundle:ShoppingListTotal')
            ->getRepository('OroB2BShoppingListBundle:ShoppingListTotal');
        foreach ($accountsData as $data) {
            $repository->invalidateByAccounts($data['accounts'], $data['websiteId']);
        }
    }

    /**
     * @param AccountGroupCPLUpdateEvent $event
     */
    public function onAccountGroupPriceListUpdate(AccountGroupCPLUpdateEvent $event)
    {
        $accountsData = $event->getAccountGroupsData();
        /** @var PriceListAccountFallbackRepository $fallbackRepository */
        $fallbackRepository = $this->registry->getManagerForClass('OroB2BPricingBundle:PriceListAccountFallback')
            ->getRepository('OroB2BPricingBundle:PriceListAccountFallback');
        /** @var ShoppingListTotalRepository $shoppingTotalsRepository */
        $shoppingTotalsRepository = $this->registry->getManagerForClass('OroB2BShoppingListBundle:ShoppingListTotal')
            ->getRepository('OroB2BShoppingListBundle:ShoppingListTotal');
        foreach ($accountsData as $data) {
            $accounts = $fallbackRepository->getAccountIdentityByGroup($data['accountGroups'], $data['websiteId']);
            $i = 0;
            $ids = [];
            foreach ($accounts as $accountData) {
                $ids[] = $accountData['id'];
                $i++;
                if ($i % self::ACCOUNT_BATCH_SIZE === 0) {
                    $shoppingTotalsRepository->invalidateByAccounts($ids, $data['websiteId']);
                    $ids = [];
                }
            }
            if (!empty($ids)) {
                $shoppingTotalsRepository->invalidateByAccounts($ids, $data['websiteId']);
            }
        }
    }

    /**
     * @param WebsiteCPLUpdateEvent $event
     */
    public function onWebsitePriceListUpdate(WebsiteCPLUpdateEvent $event)
    {
        $websiteIds = $event->getWebsiteIds();
        /** @var PriceListAccountGroupFallbackRepository $fallbackRepository */
        $fallbackRepository = $this->registry->getManagerForClass('OroB2BPricingBundle:PriceListAccountGroupFallback')
            ->getRepository('OroB2BPricingBundle:PriceListAccountGroupFallback');
        /** @var ShoppingListTotalRepository $shoppingTotalsRepository */
        $shoppingTotalsRepository = $this->registry->getManagerForClass('OroB2BShoppingListBundle:ShoppingListTotal')
            ->getRepository('OroB2BShoppingListBundle:ShoppingListTotal');
        foreach ($websiteIds as $websiteId) {
            $accounts = $fallbackRepository->getAccountIdentityByWebsite($websiteId);
            $i = 0;
            $ids = [];
            foreach ($accounts as $accountData) {
                $ids[] = $accountData['id'];
                $i++;
                if ($i % self::ACCOUNT_BATCH_SIZE === 0) {
                    $shoppingTotalsRepository->invalidateByAccounts($ids, $websiteId);
                    $ids = [];
                }
            }
            if (!empty($ids)) {
                $shoppingTotalsRepository->invalidateByAccounts($ids, $websiteId);
            }
        }
    }

    /**
     * @param ConfigCPLUpdateEvent $event
     */
    public function onConfigPriceListUpdate(ConfigCPLUpdateEvent $event)
    {
        /** @var PriceListWebsiteFallbackRepository $fallbackWebsiteRepository */
        $fallbackWebsiteRepository = $this->registry->getManagerForClass('OroB2BPricingBundle:PriceListWebsiteFallback')
            ->getRepository('OroB2BPricingBundle:PriceListWebsiteFallback');
        /** @var PriceListAccountGroupFallbackRepository $fallbackRepository */
        $fallbackRepository = $this->registry->getManagerForClass('OroB2BPricingBundle:PriceListAccountGroupFallback')
            ->getRepository('OroB2BPricingBundle:PriceListAccountGroupFallback');
        /** @var ShoppingListTotalRepository $shoppingTotalsRepository */
        $shoppingTotalsRepository = $this->registry->getManagerForClass('OroB2BShoppingListBundle:ShoppingListTotal')
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
                    $shoppingTotalsRepository->invalidateByAccounts($ids, $websiteData['id']);
                    $ids = [];
                }
            }
            if (!empty($ids)) {
                $shoppingTotalsRepository->invalidateByAccounts($ids, $websiteData['id']);
            }
        }
    }
}
