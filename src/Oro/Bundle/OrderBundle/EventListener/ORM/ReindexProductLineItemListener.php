<?php

namespace Oro\Bundle\OrderBundle\EventListener\ORM;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\ProductBundle\Manager\ProductReindexManager;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class ReindexProductLineItemListener
{
    /** @see \Oro\Bundle\OrderBundle\Entity\OrderLineItem::$product */
    const ORDER_LINE_ITEM_PRODUCT_FIELD = 'product';

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
     * @param OrderLineItem $lineItem
     * @param LifecycleEventArgs $args
     */
    public function reindexProductOnLineItemCreateOrDelete(OrderLineItem $lineItem, LifecycleEventArgs $args)
    {
        $availableStatuses = $this->statusesProvider->getAvailableStatuses();
        $orderStatus = $lineItem->getOrder()->getInternalStatus();

        if ($orderStatus && in_array($orderStatus->getId(), $availableStatuses)
            && $this->isReindexAllowed($lineItem)) {
            $product = $lineItem->getProduct();
            $websiteId = $lineItem->getOrder()->getWebsite()->getId();
            $this->reindexManager->reindexProduct($product, $websiteId);
        }
    }

    /**
     * @param OrderLineItem $lineItem
     * @param PreUpdateEventArgs $event
     */
    public function reindexProductOnLineItemUpdate(OrderLineItem $lineItem, PreUpdateEventArgs $event)
    {
        $availableStatuses = $this->statusesProvider->getAvailableStatuses();
        $orderStatus = $lineItem->getOrder()->getInternalStatus();

        if ($orderStatus && in_array($orderStatus->getId(), $availableStatuses)
            && $event->hasChangedField(static::ORDER_LINE_ITEM_PRODUCT_FIELD)
            && $this->isReindexAllowed($lineItem)) {
            $websiteId = $lineItem->getOrder()->getWebsite()->getId();
            $this->reindexManager->reindexProduct(
                $event->getOldValue(static::ORDER_LINE_ITEM_PRODUCT_FIELD),
                $websiteId
            );
            $this->reindexManager->reindexProduct(
                $event->getNewValue(static::ORDER_LINE_ITEM_PRODUCT_FIELD),
                $websiteId
            );
        }
    }

    /**
     * @param OrderLineItem $lineItem
     *
     * @return bool
     */
    protected function isReindexAllowed(OrderLineItem $lineItem)
    {
        $website = $lineItem->getOrder()->getWebsite();
        /**
         * Ignore reindex update in case when order doesn't attached to any website
         */
        if (!($website instanceof Website)) {
            return false;
        }

        return true;
    }
}
