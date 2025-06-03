<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\DependencyInjection\Configuration;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListCustomerFallbackRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListCustomerGroupFallbackRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListWebsiteFallbackRepository;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CombinedPriceListsUpdateEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\ConfigCPLUpdateEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CustomerCPLUpdateEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CustomerGroupCPLUpdateEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\WebsiteCPLUpdateEvent;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListTotalRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Listens changes of Price Lists assigned to Customers, Customer Groups, Websites
 * or changes Price List in system configuration and trigger invalidation of totals for all related Shopping Lists.
 */
class ShoppingListTotalListener
{
    const ACCOUNT_BATCH_SIZE = 500;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var int
     */
    private $anonymousCustomerGroupId;

    public function __construct(ManagerRegistry $registry, ConfigManager $configManager)
    {
        $this->registry = $registry;
        $this->configManager = $configManager;
    }

    public function onPriceListUpdate(CombinedPriceListsUpdateEvent $event)
    {
        $this->registry->getManagerForClass(ShoppingListTotal::class)
            ->getRepository(ShoppingListTotal::class)
            ->invalidateByCombinedPriceList($event->getCombinedPriceListIds());
    }

    public function onCustomerPriceListUpdate(CustomerCPLUpdateEvent $event)
    {
        $customersData = $event->getCustomersData();
        /** @var ShoppingListTotalRepository $repository */
        $repository = $this->registry->getManagerForClass(ShoppingListTotal::class)
            ->getRepository(ShoppingListTotal::class);
        foreach ($customersData as $data) {
            $repository->invalidateByCustomers($data['customers'], $data['websiteId']);
        }
    }

    public function onCustomerGroupPriceListUpdate(CustomerGroupCPLUpdateEvent $event)
    {
        $customersData = $event->getCustomerGroupsData();
        /** @var PriceListCustomerFallbackRepository $fallbackRepository */
        $fallbackRepository = $this->registry->getManagerForClass(PriceListCustomerFallback::class)
            ->getRepository(PriceListCustomerFallback::class);
        /** @var ShoppingListTotalRepository $shoppingTotalsRepository */
        $shoppingTotalsRepository = $this->registry->getManagerForClass(ShoppingListTotal::class)
            ->getRepository(ShoppingListTotal::class);
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

            $organization = $this->getOrganizationByWebsiteId($data['websiteId']);
            $this->handleGuestShoppingLists($shoppingTotalsRepository, $organization, $data);
        }
    }

    private function handleGuestShoppingLists(
        ShoppingListTotalRepository $repository,
        OrganizationInterface $organization,
        array $data
    ) {
        $anonymousCustomerGroupId = $this->getAnonymousCustomerGroupId($organization);
        if (!$anonymousCustomerGroupId) {
            return;
        }

        foreach ($data['customerGroups'] as $customerGroup) {
            if ($customerGroup instanceof CustomerGroup) {
                $customerGroup = $customerGroup->getId();
            }

            if ((int)$customerGroup === $anonymousCustomerGroupId) {
                $repository->invalidateGuestShoppingLists($data['websiteId']);

                return;
            }
        }
    }

    /**
     * @return int
     */
    private function getAnonymousCustomerGroupId(OrganizationInterface $organization)
    {
        if ($this->anonymousCustomerGroupId === null) {
            $this->anonymousCustomerGroupId = (int)$this->configManager->get(
                Configuration::getConfigKeyByName(Configuration::ANONYMOUS_CUSTOMER_GROUP),
                false,
                false,
                $organization
            );
        }

        return $this->anonymousCustomerGroupId;
    }

    public function onWebsitePriceListUpdate(WebsiteCPLUpdateEvent $event)
    {
        $websiteIds = $event->getWebsiteIds();
        /** @var PriceListCustomerGroupFallbackRepository $fallbackRepository */
        $fallbackRepository = $this->registry->getManagerForClass(PriceListCustomerGroupFallback::class)
            ->getRepository(PriceListCustomerGroupFallback::class);
        /** @var ShoppingListTotalRepository $shoppingTotalsRepository */
        $shoppingTotalsRepository = $this->registry->getManagerForClass(ShoppingListTotal::class)
            ->getRepository(ShoppingListTotal::class);
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

    public function onConfigPriceListUpdate(ConfigCPLUpdateEvent $event)
    {
        /** @var PriceListWebsiteFallbackRepository $fallbackWebsiteRepository */
        $fallbackWebsiteRepository = $this->registry->getManagerForClass(PriceListWebsiteFallback::class)
            ->getRepository(PriceListWebsiteFallback::class);
        /** @var PriceListCustomerGroupFallbackRepository $fallbackRepository */
        $fallbackRepository = $this->registry->getManagerForClass(PriceListCustomerGroupFallback::class)
            ->getRepository(PriceListCustomerGroupFallback::class);
        /** @var ShoppingListTotalRepository $shoppingTotalsRepository */
        $shoppingTotalsRepository = $this->registry->getManagerForClass(ShoppingListTotal::class)
            ->getRepository(ShoppingListTotal::class);

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

    private function getOrganizationByWebsiteId(int $websiteId): OrganizationInterface
    {
        $website = $this->registry
            ->getManagerForClass(Website::class)
            ->getRepository(Website::class)
            ->find($websiteId);

        return $website->getOrganization();
    }
}
