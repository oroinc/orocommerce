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

class ReindexProductLineItemListener
{
    use FeatureCheckerHolderTrait;

    /** @see \Oro\Bundle\OrderBundle\Entity\OrderLineItem::$product */
    const ORDER_LINE_ITEM_PRODUCT_FIELD = 'product';

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

        if ($event->hasChangedField(static::ORDER_LINE_ITEM_PRODUCT_FIELD)) {
            $websiteId = $lineItem->getOrder()->getWebsite()->getId();

            $oldProduct = $event->getOldValue(static::ORDER_LINE_ITEM_PRODUCT_FIELD);
            if ($oldProduct instanceof Product) {
                $this->productReindexManager->reindexProduct(
                    $oldProduct,
                    $websiteId
                );
            }

            $newProduct = $event->getNewValue(static::ORDER_LINE_ITEM_PRODUCT_FIELD);
            if ($newProduct instanceof Product) {
                $this->productReindexManager->reindexProduct(
                    $newProduct,
                    $websiteId
                );
            }
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
}
