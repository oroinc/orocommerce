<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Event\PricingStorage\CustomerGroupRelationUpdateEvent;
use Oro\Bundle\PricingBundle\Event\PricingStorage\CustomerRelationUpdateEvent;
use Oro\Bundle\PricingBundle\Event\PricingStorage\MassStorageUpdateEvent;
use Oro\Bundle\PricingBundle\Event\PricingStorage\WebsiteRelationUpdateEvent;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListTotalRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;

/**
 * Listens changes of Price Lists assigned to Customers, Customer Groups, Websites
 * or changes Price List in system configuration and trigger invalidation of totals for all related Shopping Lists.
 */
class FlatPricingShoppingListTotalListener
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function onPriceListUpdate(MassStorageUpdateEvent $event)
    {
        $repository = $this->getRepository();
        $repository->invalidateByPriceList($event->getPriceListIds());
    }

    public function onCustomerPriceListUpdate(CustomerRelationUpdateEvent $event)
    {
        $customersData = $event->getCustomersData();
        $repository = $this->getRepository();
        foreach ($customersData as $data) {
            $repository->invalidateByCustomers($data['customers'], $data['websiteId']);
        }
    }

    public function onCustomerGroupPriceListUpdate(CustomerGroupRelationUpdateEvent $event)
    {
        $customerGroupsData = $event->getCustomerGroupsData();
        $repository = $this->getRepository();

        foreach ($customerGroupsData as $data) {
            $repository->invalidateByCustomerGroupsForFlatPricing($data['customerGroups'], $data['websiteId']);
        }
    }

    public function onWebsitePriceListUpdate(WebsiteRelationUpdateEvent $event)
    {
        $websiteIds = $event->getWebsiteIds();
        $this->getRepository()->invalidateByWebsitesForFlatPricing($websiteIds);
    }

    private function getRepository(): ShoppingListTotalRepository
    {
        return $this->registry
            ->getManagerForClass(ShoppingListTotal::class)
            ->getRepository(ShoppingListTotal::class);
    }
}
