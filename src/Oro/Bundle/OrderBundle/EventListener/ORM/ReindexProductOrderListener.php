<?php

namespace Oro\Bundle\OrderBundle\EventListener\ORM;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Bundle\WebsiteSearchBundle\Provider\ReindexationWebsiteProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
    private const INTERNAL_STATUS_FIELD = 'internal_status';
    /** @see \Oro\Bundle\OrderBundle\Entity\Order::$website */
    private const WEBSITE_FIELD = 'website';
    /** @see \Oro\Bundle\OrderBundle\Entity\Order::$customerUser */
    private const CUSTOMER_USER_FIELD = 'customerUser';
    /** @see \Oro\Bundle\OrderBundle\Entity\Order::$createdAt */
    private const CREATED_AT_FIELD = 'createdAt';

    private EventDispatcherInterface $eventDispatcher;
    private OrderStatusesProviderInterface $statusesProvider;
    private ReindexationWebsiteProviderInterface $reindexationWebsiteProvider;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        OrderStatusesProviderInterface $statusesProvider,
        ReindexationWebsiteProviderInterface $websiteProvider
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->statusesProvider = $statusesProvider;
        $this->reindexationWebsiteProvider = $websiteProvider;
    }

    public function processOrderUpdate(Order $order, PreUpdateEventArgs $event): void
    {
        $websites = [];
        $website = $order->getWebsite();
        if ($this->isInternalStatusChanged($order, $event)) {
            $websites[] = $website;
        } elseif ($this->isCustomerUserChanged($order, $event)) {
            $websites[] = $website;
        } elseif ($this->isCreatedAtChanged($order, $event)) {
            $websites[] = $website;
        }
        if ($this->isWebsiteChanged($order, $event)) {
            if (!$websites && $this->isFeaturesEnabledForWebsite($website)) {
                $websites[] = $website;
            }
            $oldWebsite = $event->getOldValue(self::WEBSITE_FIELD);
            if ($this->isFeaturesEnabledForWebsite($oldWebsite)) {
                $websites[] = $oldWebsite;
            }
        }
        if ($websites) {
            $this->reindexOrderProducts($order, $websites);
        }
    }

    public function processOrderRemove(Order $order): void
    {
        $website = $order->getWebsite();
        if ($this->isFeaturesEnabledForWebsite($website) && $this->isAllowedStatus($order->getInternalStatus())) {
            $this->reindexOrderProducts($order, [$website]);
        }
    }

    private function reindexOrderProducts(Order $order, array $websites): void
    {
        $productIds = [];
        foreach ($order->getProductsFromLineItems() as $product) {
            $productIds[] = $product->getId();
        }
        foreach ($order->getLineItems() as $lineItem) {
            $parentProduct = $lineItem->getParentProduct();
            if (null !== $parentProduct) {
                $productIds[] = $parentProduct->getId();
            }
        }
        $productIds = array_unique($productIds);

        $websiteIds = [];
        foreach ($websites as $website) {
            $websiteIds[] = $this->reindexationWebsiteProvider->getReindexationWebsiteIds($website);
        }
        $websiteIds = array_unique(array_merge(...$websiteIds));

        $this->eventDispatcher->dispatch(
            new ReindexationRequestEvent([Product::class], $websiteIds, $productIds, true, ['order']),
            ReindexationRequestEvent::EVENT_NAME
        );
    }

    private function isInternalStatusChanged(Order $order, PreUpdateEventArgs $event): bool
    {
        return
            $event->hasChangedField(self::INTERNAL_STATUS_FIELD)
            && $this->isFeaturesEnabledForWebsite($order->getWebsite())
            && (
                $this->isAllowedStatus($event->getNewValue(self::INTERNAL_STATUS_FIELD))
                xor $this->isAllowedStatus($event->getOldValue(self::INTERNAL_STATUS_FIELD))
            );
    }

    private function isCustomerUserChanged(Order $order, PreUpdateEventArgs $event): bool
    {
        return
            $event->hasChangedField(self::CUSTOMER_USER_FIELD)
            && $this->isFeaturesEnabledForWebsite($order->getWebsite())
            && $this->isAllowedStatus($order->getInternalStatus());
    }

    private function isCreatedAtChanged(Order $order, PreUpdateEventArgs $event): bool
    {
        return
            $event->hasChangedField(self::CREATED_AT_FIELD)
            && $this->isFeaturesEnabledForWebsite($order->getWebsite())
            && $this->isAllowedStatus($order->getInternalStatus());
    }

    private function isWebsiteChanged(Order $order, PreUpdateEventArgs $event): bool
    {
        return
            $event->hasChangedField(self::WEBSITE_FIELD)
            && $this->isAllowedStatus($order->getInternalStatus());
    }

    private function isAllowedStatus(?AbstractEnumValue $status): bool
    {
        return
            null !== $status
            && \in_array($status->getId(), $this->statusesProvider->getAvailableStatuses(), true);
    }

    private function isFeaturesEnabledForWebsite(?Website $website): bool
    {
        return null !== $website && $this->isFeaturesEnabled($website);
    }
}
