<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\PricingBundle\Entity\Repository\PriceListCustomerFallbackRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListCustomerGroupFallbackRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListWebsiteFallbackRepository;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CustomerCPLUpdateEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CustomerGroupCPLUpdateEvent;
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
     * @param CustomerCPLUpdateEvent $event
     */
    public function onCustomerPriceListUpdate(CustomerCPLUpdateEvent $event)
    {
        $customersData = $event->getCustomersData();
        /** @var ShoppingListTotalRepository $repository */
        $repository = $this->registry->getManagerForClass('OroShoppingListBundle:ShoppingListTotal')
            ->getRepository('OroShoppingListBundle:ShoppingListTotal');
        foreach ($customersData as $data) {
            $repository->invalidateByCustomers($data['customers'], $data['websiteId']);
        }
    }

    /**
     * @param CustomerGroupCPLUpdateEvent $event
     */
    public function onCustomerGroupPriceListUpdate(CustomerGroupCPLUpdateEvent $event)
    {
        $customersData = $event->getCustomerGroupsData();
        /** @var PriceListCustomerFallbackRepository $fallbackRepository */
        $fallbackRepository = $this->registry->getManagerForClass('OroPricingBundle:PriceListCustomerFallback')
            ->getRepository('OroPricingBundle:PriceListCustomerFallback');
        /** @var ShoppingListTotalRepository $shoppingTotalsRepository */
        $shoppingTotalsRepository = $this->registry->getManagerForClass('OroShoppingListBundle:ShoppingListTotal')
            ->getRepository('OroShoppingListBundle:ShoppingListTotal');
        foreach ($customersData as $data) {
            $customers = $fallbackRepository->getCustomerIdentityByGroup($data['customerGroups'], $data['websiteId']);
            $i = 0;
            $ids = [];
            foreach ($customers as $customerData) {
                $ids[] = $customerData['id'];
                $i++;
                if ($i % self::ACCOUNT_BATCH_SIZE === 0) {
                    $shoppingTotalsRepository->invalidateByCustomers($ids, $data['websiteId']);
                    $ids = [];
                }
            }
            if (!empty($ids)) {
                $shoppingTotalsRepository->invalidateByCustomers($ids, $data['websiteId']);
            }
        }
    }

    /**
     * @param WebsiteCPLUpdateEvent $event
     */
    public function onWebsitePriceListUpdate(WebsiteCPLUpdateEvent $event)
    {
        $websiteIds = $event->getWebsiteIds();
        /** @var PriceListCustomerGroupFallbackRepository $fallbackRepository */
        $fallbackRepository = $this->registry->getManagerForClass('OroPricingBundle:PriceListCustomerGroupFallback')
            ->getRepository('OroPricingBundle:PriceListCustomerGroupFallback');
        /** @var ShoppingListTotalRepository $shoppingTotalsRepository */
        $shoppingTotalsRepository = $this->registry->getManagerForClass('OroShoppingListBundle:ShoppingListTotal')
            ->getRepository('OroShoppingListBundle:ShoppingListTotal');
        foreach ($websiteIds as $websiteId) {
            $customers = $fallbackRepository->getCustomerIdentityByWebsite($websiteId);
            $i = 0;
            $ids = [];
            foreach ($customers as $customerData) {
                $ids[] = $customerData['id'];
                $i++;
                if ($i % self::ACCOUNT_BATCH_SIZE === 0) {
                    $shoppingTotalsRepository->invalidateByCustomers($ids, $websiteId);
                    $ids = [];
                }
            }
            if (!empty($ids)) {
                $shoppingTotalsRepository->invalidateByCustomers($ids, $websiteId);
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
        /** @var PriceListCustomerGroupFallbackRepository $fallbackRepository */
        $fallbackRepository = $this->registry->getManagerForClass('OroPricingBundle:PriceListCustomerGroupFallback')
            ->getRepository('OroPricingBundle:PriceListCustomerGroupFallback');
        /** @var ShoppingListTotalRepository $shoppingTotalsRepository */
        $shoppingTotalsRepository = $this->registry->getManagerForClass('OroShoppingListBundle:ShoppingListTotal')
            ->getRepository('OroShoppingListBundle:ShoppingListTotal');

        $websitesData = $fallbackWebsiteRepository->getWebsiteIdByDefaultFallback();
        foreach ($websitesData as $websiteData) {
            $customers = $fallbackRepository->getCustomerIdentityByWebsite($websiteData['id']);
            $i = 0;
            $ids = [];
            foreach ($customers as $customerData) {
                $ids[] = $customerData['id'];
                $i++;
                if ($i % self::ACCOUNT_BATCH_SIZE === 0) {
                    $shoppingTotalsRepository->invalidateByCustomers($ids, $websiteData['id']);
                    $ids = [];
                }
            }
            if (!empty($ids)) {
                $shoppingTotalsRepository->invalidateByCustomers($ids, $websiteData['id']);
            }
        }
    }
}
