<?php

namespace Oro\Bundle\OrderBundle\EventListener\ORM;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Search\Reindex\ProductReindexManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Reindexes related products when line item is created, updated or deleted.
 */
class ReindexProductLineItemListener
{
    use FeatureCheckerHolderTrait;

    /** @see \Oro\Bundle\OrderBundle\Entity\OrderLineItem::$product */
    const ORDER_LINE_ITEM_PRODUCT_FIELD = 'product';

    /** @see \Oro\Bundle\OrderBundle\Entity\OrderLineItem::$parentProduct */
    const ORDER_LINE_ITEM_PARENT_PRODUCT_FIELD = 'parentProduct';

    /**
     * @var ProductReindexManager
     */
    protected $productReindexManager;

    /**
     * @var OrderStatusesProviderInterface
     */
    protected $statusesProvider;

    /**
     * @param ProductReindexManager        $productReindexManager
     * @param OrderStatusesProviderInterface $statusesProvider
     */
    public function __construct(
        ProductReindexManager $productReindexManager,
        OrderStatusesProviderInterface $statusesProvider
    ) {
        $this->productReindexManager = $productReindexManager;
        $this->statusesProvider = $statusesProvider;
    }

    /**
     * @param OrderLineItem $lineItem
     */
    public function reindexProductOnLineItemCreateOrDelete(OrderLineItem $lineItem)
    {
        if (!$this->isReindexAllowed($lineItem)) {
            return;
        }

        $product = $lineItem->getProduct();
        if (!($product instanceof Product)) {
            return;
        }

        $websiteId = $lineItem->getOrder()->getWebsite()->getId();
        $this->productReindexManager->reindexProduct($product, $websiteId);
        if ($lineItem->getParentProduct()) {
            $this->productReindexManager->reindexProduct($lineItem->getParentProduct(), $websiteId);
        }
    }

    /**
     * @param OrderLineItem $lineItem
     * @param PreUpdateEventArgs $event
     */
    public function reindexProductOnLineItemUpdate(OrderLineItem $lineItem, PreUpdateEventArgs $event)
    {
        if (!$this->isReindexAllowed($lineItem)) {
            return;
        }

        $websiteId = $lineItem->getOrder()->getWebsite()->getId();

        $this->reindexFieldProduct($event, static::ORDER_LINE_ITEM_PRODUCT_FIELD, $websiteId);
        $this->reindexFieldProduct($event, static::ORDER_LINE_ITEM_PARENT_PRODUCT_FIELD, $websiteId);
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

        if (!$this->isFeaturesEnabled($website)) {
            return false;
        }

        $availableStatuses = $this->statusesProvider->getAvailableStatuses();
        $orderStatus = $lineItem->getOrder()->getInternalStatus();
        if (!$orderStatus instanceof AbstractEnumValue || !in_array($orderStatus->getId(), $availableStatuses)) {
            return false;
        }

        return true;
    }

    /**
     * @param PreUpdateEventArgs $event
     * @param string $field
     * @param int $websiteId
     */
    private function reindexFieldProduct(PreUpdateEventArgs $event, string $field, int $websiteId)
    {
        if (!$event->hasChangedField($field)) {
            return;
        }

        $oldProduct = $event->getOldValue($field);
        if ($oldProduct instanceof Product) {
            $this->productReindexManager->reindexProduct($oldProduct, $websiteId);
        }

        $newProduct = $event->getNewValue($field);
        if ($newProduct instanceof Product) {
            $this->productReindexManager->reindexProduct($newProduct, $websiteId);
        }
    }
}
