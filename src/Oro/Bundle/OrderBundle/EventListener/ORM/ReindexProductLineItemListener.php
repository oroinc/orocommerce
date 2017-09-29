<?php

namespace Oro\Bundle\OrderBundle\EventListener\ORM;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

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
     * @param ProductReindexManager $reindexManager
     */
    public function __construct(ProductReindexManager $reindexManager)
    {
        $this->reindexManager = $reindexManager;
    }
    /**
     * @param OrderLineItem $lineItem
     * @param LifecycleEventArgs $args
     */
    public function reindexProductOnLineItemCreateOrDelete(OrderLineItem $lineItem, LifecycleEventArgs $args)
    {
        $website = $lineItem->getOrder()->getWebsite();

        if ($this->isReindexAllowed($lineItem)) {
            $product = $lineItem->getProduct();
            $websiteId = $website->getId();
            $this->reindexManager->reindexProduct($product, $websiteId);
        }
    }

    /**
     * @param OrderLineItem $lineItem
     * @param PreUpdateEventArgs $event
     */
    public function reindexProductOnLineItemUpdate(OrderLineItem $lineItem, PreUpdateEventArgs $event)
    {
        if ($event->hasChangedField(static::ORDER_LINE_ITEM_PRODUCT_FIELD) && $this->isReindexAllowed($lineItem)) {
            $website = $lineItem->getOrder()->getWebsite();
            $websiteId = $website->getId();
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
