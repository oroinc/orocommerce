<?php

namespace Oro\Bundle\OrderBundle\EventListener\ORM;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\ProductBundle\Search\Reindex\ProductReindexManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Reindexes related products when order is updated or removed.
 */
class ReindexProductOrderListener
{
    use FeatureCheckerHolderTrait;

    /**
     * This is extended field
     * @see \Oro\Bundle\OrderBundle\Model\ExtendOrder::getInternalStatus()
     */
    const ORDER_INTERNAL_STATUS_FIELD = 'internal_status';

    /**
     * @see \Oro\Bundle\OrderBundle\Entity\Order::$website
     */
    const ORDER_INTERNAL_WEBSITE_FIELD = 'website';

    /**
     * This is extended field
     * @see \Oro\Bundle\OrderBundle\Entity\Order::$customerUser
     */
    const ORDER_CUSTOMER_USER_FIELD = 'customerUser';

    /**
     * @see \Oro\Bundle\OrderBundle\Entity\Order::$createdAt
     */
    const ORDER_CREATED_AT_FIELD = 'createdAt';

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
     * @param Order $order
     * @param PreUpdateEventArgs $event
     */
    protected function onStatusChange(Order $order, PreUpdateEventArgs $event)
    {
        if ($event->hasChangedField(static::ORDER_INTERNAL_STATUS_FIELD)) {
            $oldStatus = $event->getOldValue(static::ORDER_INTERNAL_STATUS_FIELD);
            $newStatus = $event->getNewValue(static::ORDER_INTERNAL_STATUS_FIELD);
            if ($this->isOrderStatusChangedForReindex($oldStatus, $newStatus)) {
                $this->reindexProductsInOrderWithoutStatusChecking($order, $order->getWebsite());
            }
        }
    }

    /**
     * @param Order $order
     * @param PreUpdateEventArgs $event
     */
    protected function onWebsiteChange(Order $order, PreUpdateEventArgs $event)
    {
        if ($event->hasChangedField(static::ORDER_INTERNAL_WEBSITE_FIELD)) {
            $oldWebsite = $event->getOldValue(static::ORDER_INTERNAL_WEBSITE_FIELD);
            $newWebsite = $event->getNewValue(static::ORDER_INTERNAL_WEBSITE_FIELD);
            $this->reindexProductsInOrderWithinWebsite($order, $oldWebsite);
            $this->reindexProductsInOrderWithinWebsite($order, $newWebsite);
        }
    }

    /**
     * @param Order              $order
     * @param PreUpdateEventArgs $event
     */
    protected function onCustomerUserChange(Order $order, PreUpdateEventArgs $event)
    {
        if ($event->hasChangedField(static::ORDER_CUSTOMER_USER_FIELD)) {
            $this->reindexProductsInOrderWithinWebsite($order, $order->getWebsite());
        }
    }

    /**
     * @param Order              $order
     * @param PreUpdateEventArgs $event
     */
    protected function onCreatedAtChange(Order $order, PreUpdateEventArgs $event)
    {
        if ($event->hasChangedField(static::ORDER_CREATED_AT_FIELD)) {
            $this->reindexProductsInOrderWithinWebsite($order, $order->getWebsite());
        }
    }

    /**
     * @param Order              $order
     * @param PreUpdateEventArgs $event
     */
    public function processOrderUpdate(Order $order, PreUpdateEventArgs $event)
    {
        $this->onStatusChange($order, $event);
        $this->onCreatedAtChange($order, $event);
        $this->onWebsiteChange($order, $event);
        $this->onCustomerUserChange($order, $event);
    }

    /**
     * @param Order $order
     */
    public function processOrderRemove(Order $order)
    {
        $this->reindexProductsInOrderWithinWebsite($order, $order->getWebsite());
    }

    /**
     * @param Order        $order
     * @param Website|null $website
     */
    protected function reindexProductsInOrderWithinWebsite(Order $order, Website $website = null)
    {
        $orderStatus = $order->getInternalStatus();
        if (!$this->isAllowedStatus($orderStatus)) {
            return;
        }

        $this->reindexProductsInOrderWithoutStatusChecking($order, $website);
    }

    /**
     * @param Order $order
     * @param Website|null $website
     */
    protected function reindexProductsInOrderWithoutStatusChecking(Order $order, Website $website = null)
    {
        if (!$this->isReindexAllowed($website)) {
            return;
        }

        $productIds = [];
        $websiteId = $website->getId();
        foreach ($order->getProductsFromLineItems() as $product) {
            $productIds[] = $product->getId();
        }

        $this->productReindexManager->reindexProducts($productIds, $websiteId);
        $this->productReindexManager->reindexProducts($this->getParentProductIds($order), $websiteId);
    }

    /**
     * @param AbstractEnumValue|null $oldStatus
     * @param AbstractEnumValue|null $newStatus
     * @return bool
     */
    protected function isOrderStatusChangedForReindex(
        AbstractEnumValue $oldStatus = null,
        AbstractEnumValue $newStatus = null
    ) {
        return ($this->isAllowedStatus($newStatus) && !$this->isAllowedStatus($oldStatus))
            || (!$this->isAllowedStatus($newStatus) && $this->isAllowedStatus($oldStatus));
    }

    /**
     * @param AbstractEnumValue|null $status
     * @return bool
     */
    protected function isAllowedStatus(AbstractEnumValue $status = null)
    {
        $availableStatuses = $this->statusesProvider->getAvailableStatuses();
        $statusId = $status !== null ? $status->getId() : null;

        return in_array($statusId, $availableStatuses);
    }

    /**
     * @param Website|null $website
     * @return bool
     */
    protected function isReindexAllowed(Website $website = null)
    {
        /**
         * Ignore reindex update in case when order doesn't attached to any website
         */
        if (!($website instanceof Website)) {
            return false;
        }

        if (!$this->isFeaturesEnabled($website)) {
            return false;
        }

        return true;
    }

    /**
     * @param Order $order
     * @return array
     */
    private function getParentProductIds(Order $order): array
    {
        $ids = [];
        foreach ($order->getLineItems() as $lineItem) {
            if ($lineItem->getParentProduct()) {
                $ids[] = $lineItem->getParentProduct()->getId();
            }
        }

        return $ids;
    }
}
