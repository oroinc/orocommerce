<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\PricingBundle\Entity\Repository\PriceListAccountFallbackRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListAccountGroupFallbackRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListWebsiteFallbackRepository;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\AccountCPLUpdateEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\AccountGroupCPLUpdateEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\WebsiteCPLUpdateEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\ConfigCPLUpdateEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CombinedPriceListsUpdateEvent;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListTotalRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;

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
        $this->registry->getManagerForClass('OroShoppingListBundle:ShoppingListTotal')
            ->getRepository('OroShoppingListBundle:ShoppingListTotal')
            ->invalidateByCpl($event->getCombinedPriceListIds());
    }

    /**
     * @param AccountCPLUpdateEvent $event
     */
    public function onAccountPriceListUpdate(AccountCPLUpdateEvent $event)
    {
        $accountsData = $event->getAccountsData();
        /** @var ShoppingListTotalRepository $repository */
        $repository = $this->registry->getManagerForClass('OroShoppingListBundle:ShoppingListTotal')
            ->getRepository('OroShoppingListBundle:ShoppingListTotal');
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
        $fallbackRepository = $this->registry->getManagerForClass('OroPricingBundle:PriceListAccountFallback')
            ->getRepository('OroPricingBundle:PriceListAccountFallback');
        /** @var ShoppingListTotalRepository $shoppingTotalsRepository */
        $shoppingTotalsRepository = $this->registry->getManagerForClass('OroShoppingListBundle:ShoppingListTotal')
            ->getRepository('OroShoppingListBundle:ShoppingListTotal');
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
        $fallbackRepository = $this->registry->getManagerForClass('OroPricingBundle:PriceListAccountGroupFallback')
            ->getRepository('OroPricingBundle:PriceListAccountGroupFallback');
        /** @var ShoppingListTotalRepository $shoppingTotalsRepository */
        $shoppingTotalsRepository = $this->registry->getManagerForClass('OroShoppingListBundle:ShoppingListTotal')
            ->getRepository('OroShoppingListBundle:ShoppingListTotal');
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
        $fallbackWebsiteRepository = $this->registry->getManagerForClass('OroPricingBundle:PriceListWebsiteFallback')
            ->getRepository('OroPricingBundle:PriceListWebsiteFallback');
        /** @var PriceListAccountGroupFallbackRepository $fallbackRepository */
        $fallbackRepository = $this->registry->getManagerForClass('OroPricingBundle:PriceListAccountGroupFallback')
            ->getRepository('OroPricingBundle:PriceListAccountGroupFallback');
        /** @var ShoppingListTotalRepository $shoppingTotalsRepository */
        $shoppingTotalsRepository = $this->registry->getManagerForClass('OroShoppingListBundle:ShoppingListTotal')
            ->getRepository('OroShoppingListBundle:ShoppingListTotal');

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
