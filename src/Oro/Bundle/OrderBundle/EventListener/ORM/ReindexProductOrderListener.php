<?php

namespace Oro\Bundle\OrderBundle\EventListener\ORM;

use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\ProductBundle\Manager\ProductReindexManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class ReindexProductOrderListener
{
    /**
     * This is extended field
     * @see \Oro\Bundle\OrderBundle\Model\ExtendOrder::getInternalStatus()
     */
    const ORDER_INTERNAL_STATUS_FIELD = 'internal_status';

    /**
     * @var ProductReindexManager
     */
    protected $reindexManager;

    /**
     * @var OrderStatusesProviderInterface
     */
    protected $statusesProvider;

    /**
     * @param ProductReindexManager $reindexManager
     * @param OrderStatusesProviderInterface $statusesProvider
     */
    public function __construct(
        ProductReindexManager $reindexManager,
        OrderStatusesProviderInterface $statusesProvider
    ) {
        $this->reindexManager = $reindexManager;
        $this->statusesProvider = $statusesProvider;
    }

    /**
     * @param Order $order
     */
    public function processIndexOnOrderStatusChange(Order $order, PreUpdateEventArgs $event)
    {
        if ($event->hasChangedField(static::ORDER_INTERNAL_STATUS_FIELD)) {
            $oldStatus = $event->getOldValue(static::ORDER_INTERNAL_STATUS_FIELD);
            $newStatus = $event->getNewValue(static::ORDER_INTERNAL_STATUS_FIELD);
            if ($this->isOrderStatusChangedForReindex($oldStatus, $newStatus)) {
                $this->reindexProductsInOrder($order);
            }
        }
    }

    /**
     * @param Order $order
     */
    public function reindexProductsInOrder(Order $order)
    {
        if (!($this->isReindexAllowed($order))) {
            return;
        }

        $productIds = [];
        $websiteId = $order->getWebsite()->getId();
        $lineItems = $order->getProductsFromLineItems();
        foreach ($lineItems as $product) {
            $productIds[] = $product->getId();
        }

        $this->reindexManager->triggerReindexationRequestEvent($productIds, $websiteId);
    }

    /**
     * @param string $oldStatus
     * @param string $newStatus
     * @return bool
     */
    protected function isOrderStatusChangedForReindex($oldStatus, $newStatus)
    {
        $availableStatuses = $this->statusesProvider->getAvailableStatuses();
        return (in_array($newStatus, $availableStatuses) && !in_array($oldStatus, $availableStatuses))
            || (!in_array($newStatus, $availableStatuses) && in_array($oldStatus, $availableStatuses));
    }

    /**
     * @param Order $order
     *
     * @return bool
     */
    protected function isReindexAllowed(Order $order)
    {
        $website = $order->getWebsite();
        /**
         * Ignore reindex update in case when order doesn't attached to any website
         */
        if (!($website instanceof Website)) {
            return false;
        }

        return true;
    }
}
