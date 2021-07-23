<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Async\Topics;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSubtotal;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutSubtotalRepository;
use Oro\Bundle\PricingBundle\Event\PricingStorage\CustomerGroupRelationUpdateEvent;
use Oro\Bundle\PricingBundle\Event\PricingStorage\CustomerRelationUpdateEvent;
use Oro\Bundle\PricingBundle\Event\PricingStorage\MassStorageUpdateEvent;
use Oro\Bundle\PricingBundle\Event\PricingStorage\WebsiteRelationUpdateEvent;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Invalidate Checkout Subtotal when it's no longer valid
 */
class FlatPricingCheckoutSubtotalListener
{
    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @var MessageProducerInterface
     */
    private $messageProducer;

    public function __construct(
        ManagerRegistry $registry,
        MessageProducerInterface $messageProducer
    ) {
        $this->registry = $registry;
        $this->messageProducer = $messageProducer;
    }

    public function onPriceListUpdate(MassStorageUpdateEvent $event)
    {
        $this->getRepository()->invalidateByPriceList($event->getPriceListIds());

        $this->recalculateSubtotals();
    }

    public function onCustomerPriceListUpdate(CustomerRelationUpdateEvent $event)
    {
        $customersData = $event->getCustomersData();
        $repository = $this->getRepository();

        foreach ($customersData as $data) {
            $repository->invalidateByCustomers($data['customers'], $data['websiteId']);
        }

        $this->recalculateSubtotals();
    }

    public function onCustomerGroupPriceListUpdate(CustomerGroupRelationUpdateEvent $event)
    {
        $customerGroupsData = $event->getCustomerGroupsData();
        $repository = $this->getRepository();

        foreach ($customerGroupsData as $data) {
            $repository->invalidateByCustomerGroups($data['customerGroups'], $data['websiteId']);
        }

        $this->recalculateSubtotals();
    }

    public function onWebsitePriceListUpdate(WebsiteRelationUpdateEvent $event)
    {
        $websiteIds = $event->getWebsiteIds();
        $this->getRepository()->invalidateByWebsites($websiteIds);

        $this->recalculateSubtotals();
    }

    /**
     * @return CheckoutSubtotalRepository
     */
    private function getRepository()
    {
        return $this->registry
            ->getManagerForClass(CheckoutSubtotal::class)
            ->getRepository(CheckoutSubtotal::class);
    }

    private function recalculateSubtotals()
    {
        $message = new Message();
        $this->messageProducer->send(Topics::RECALCULATE_CHECKOUT_SUBTOTALS, $message);
    }
}
