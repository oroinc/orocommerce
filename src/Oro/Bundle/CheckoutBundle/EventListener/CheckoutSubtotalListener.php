<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CheckoutBundle\Async\Topics;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSubtotal;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutSubtotalRepository;
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
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Invalidate Checkout Subtotal when it's no longer valid
 */
class CheckoutSubtotalListener
{
    const ACCOUNT_BATCH_SIZE = 500;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var MessageProducerInterface */
    protected $messageProducer;

    public function __construct(ManagerRegistry $registry, MessageProducerInterface $messageProducer)
    {
        $this->registry = $registry;
        $this->messageProducer = $messageProducer;
    }

    public function onPriceListUpdate(CombinedPriceListsUpdateEvent $event)
    {
        /** @var CheckoutSubtotalRepository $repository */
        $repository = $this->getRepository(CheckoutSubtotal::class);
        $repository->invalidateByCombinedPriceList($event->getCombinedPriceListIds());

        $this->recalculateSubtotals();
    }

    public function onCustomerPriceListUpdate(CustomerCPLUpdateEvent $event)
    {
        $customersData = $event->getCustomersData();
        /** @var CheckoutSubtotalRepository $repository */
        $repository = $this->getRepository(CheckoutSubtotal::class);
        foreach ($customersData as $data) {
            $repository->invalidateByCustomers($data['customers'], $data['websiteId']);
        }

        $this->recalculateSubtotals();
    }

    public function onCustomerGroupPriceListUpdate(CustomerGroupCPLUpdateEvent $event)
    {
        $customersData = $event->getCustomerGroupsData();
        /** @var PriceListCustomerFallbackRepository $fallbackRepository */
        $fallbackRepository = $this->getRepository(PriceListCustomerFallback::class);
        foreach ($customersData as $data) {
            $customers = $fallbackRepository->getCustomerIdentityByGroup($data['customerGroups'], $data['websiteId']);
            $this->invalidateSubtotalsByCustomers($customers, $data['websiteId']);
        }

        $this->recalculateSubtotals();
    }

    public function onWebsitePriceListUpdate(WebsiteCPLUpdateEvent $event)
    {
        $websiteIds = $event->getWebsiteIds();
        /** @var PriceListCustomerGroupFallbackRepository $fallbackRepository */
        $fallbackRepository = $this->getRepository(PriceListCustomerGroupFallback::class);
        foreach ($websiteIds as $websiteId) {
            $customers = $fallbackRepository->getCustomerIdentityByWebsite($websiteId);
            $this->invalidateSubtotalsByCustomers($customers, $websiteId);
        }

        $this->recalculateSubtotals();
    }

    public function onConfigPriceListUpdate(ConfigCPLUpdateEvent $event)
    {
        /** @var PriceListWebsiteFallbackRepository $fallbackWebsiteRepository */
        $fallbackWebsiteRepository = $this->getRepository(PriceListWebsiteFallback::class);
        /** @var PriceListCustomerGroupFallbackRepository $fallbackRepository */
        $fallbackRepository = $this->getRepository(PriceListCustomerGroupFallback::class);
        $websitesData = $fallbackWebsiteRepository->getWebsiteIdByDefaultFallback();
        foreach ($websitesData as $websiteData) {
            $customers = $fallbackRepository->getCustomerIdentityByWebsite($websiteData['id']);
            $this->invalidateSubtotalsByCustomers($customers, $websiteData['id']);
        }

        $this->recalculateSubtotals();
    }

    /**
     * @param string $className
     * @return ObjectRepository
     */
    protected function getRepository($className)
    {
        return $this->registry
            ->getManagerForClass($className)
            ->getRepository($className);
    }

    protected function recalculateSubtotals()
    {
        $message = new Message();
        $this->messageProducer->send(Topics::RECALCULATE_CHECKOUT_SUBTOTALS, $message);
    }

    /**
     * @param \Iterator $customers
     * @param int $websiteId
     */
    protected function invalidateSubtotalsByCustomers(\Iterator $customers, $websiteId)
    {
        /** @var CheckoutSubtotalRepository $subtotalRepository */
        $subtotalRepository = $this->getRepository(CheckoutSubtotal::class);
        $ids = [];
        foreach ($customers as $customerData) {
            $ids[] = $customerData['id'];
            if (self::ACCOUNT_BATCH_SIZE === count($ids)) {
                $subtotalRepository->invalidateByCustomers($ids, $websiteId);
                $ids = [];
            }
        }
        if (!empty($ids)) {
            $subtotalRepository->invalidateByCustomers($ids, $websiteId);
        }
    }
}
