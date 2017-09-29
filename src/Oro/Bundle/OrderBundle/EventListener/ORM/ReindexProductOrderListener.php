<?php

namespace Oro\Bundle\OrderBundle\EventListener\ORM;

use Doctrine\ORM\Event\PreUpdateEventArgs;

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
     * @param ProductReindexManager $reindexManager
     */
    public function __construct(ProductReindexManager $reindexManager)
    {
        $this->reindexManager = $reindexManager;
    }

    /**
     * @param Order $order
     */
    public function processIndexOnOrderStatusChange(Order $order, PreUpdateEventArgs $event)
    {
        if ($event->hasChangedField(static::ORDER_INTERNAL_STATUS_FIELD) && $this->isReindexAllowed($order)) {
            $internalStatusId = $order->getInternalStatus()->getId();
            if ($internalStatusId == Order::INTERNAL_STATUS_ARCHIVED
                || $internalStatusId == Order::INTERNAL_STATUS_CLOSED) {
                $this->reindexProductsInOrder($order);
            }
        }
    }

    /**
     * @param Order $order
     */
    public function reindexProductsInOrder(Order $order)
    {
        $productIds = [];
        $website = $order->getWebsite();
        $websiteId = $website->getId();
        foreach ($order->getProductsFromLineItems() as $product) {
            $productIds[] = $product->getId();
        }

        $this->reindexManager->triggerReindexationRequestEvent($productIds, $websiteId);
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
