<?php

namespace Oro\Bundle\OrderBundle\EventListener\ORM;

use Doctrine\ORM\Event\PreUpdateEventArgs;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\ProductBundle\Manager\ProductReindexManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\WebsiteBundle\Entity\Website;

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
     * @param PreUpdateEventArgs $event
     */
    public function processIndexOnOrderStatusChange(Order $order, PreUpdateEventArgs $event)
    {
        if ($event->hasChangedField(static::ORDER_INTERNAL_STATUS_FIELD)) {
            $oldStatus = $event->getOldValue(static::ORDER_INTERNAL_STATUS_FIELD);
            $newStatus = $event->getNewValue(static::ORDER_INTERNAL_STATUS_FIELD);
            if ($this->isOrderStatusChangedForReindex($oldStatus, $newStatus)) {
                $website = $order->getWebsite();
                $this->reindexProductsInOrder($order, $website);
            }
        }
    }

    /**
     * @param Order $order
     * @param PreUpdateEventArgs $event
     */
    public function processIndexOnOrderWebsiteChange(Order $order, PreUpdateEventArgs $event)
    {
        if ($event->hasChangedField(static::ORDER_INTERNAL_WEBSITE_FIELD)) {
            $orderStatus = $order->getInternalStatus();
            if (!$this->isAllowedStatus($orderStatus)) {
                return;
            }

            $oldWebsite = $event->getOldValue(static::ORDER_INTERNAL_WEBSITE_FIELD);
            $newWebsite = $event->getNewValue(static::ORDER_INTERNAL_WEBSITE_FIELD);
            $this->reindexProductsInOrder($order, $oldWebsite);
            $this->reindexProductsInOrder($order, $newWebsite);
        }
    }

    /**
     * @param Order $order
     */
    public function processOrderRemove(Order $order)
    {
        $orderStatus = $order->getInternalStatus();
        if (!$this->isAllowedStatus($orderStatus)) {
            return;
        }

        $website = $order->getWebsite();
        $this->reindexProductsInOrder($order, $website);
    }

    /**
     * @param Order $order
     * @param Website|null $website
     */
    protected function reindexProductsInOrder(Order $order, Website $website = null)
    {
        if (!$this->isReindexAllowed($website)) {
            return;
        }

        $productIds = [];
        $websiteId = $order->getWebsite()->getId();
        foreach ($order->getProductsFromLineItems() as $product) {
            $productIds[] = $product->getId();
        }

        $this->reindexManager->triggerReindexationRequestEvent($productIds, $websiteId);
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
}
