<?php

namespace Oro\Bundle\OrderBundle\EventListener\ORM;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Bundle\WebsiteSearchBundle\Provider\ReindexationWebsiteProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Reindexes related products when line item is created, updated or deleted.
 */
class ReindexProductLineItemListener
{
    use FeatureCheckerHolderTrait;

    /** @see \Oro\Bundle\OrderBundle\Entity\OrderLineItem::$product */
    private const PRODUCT_FIELD = 'product';
    /** @see \Oro\Bundle\OrderBundle\Entity\OrderLineItem::$parentProduct */
    private const PARENT_PRODUCT_FIELD = 'parentProduct';

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

    public function reindexProductOnLineItemCreateOrDelete(OrderLineItem $lineItem): void
    {
        if (!$this->isReindexAllowed($lineItem)) {
            return;
        }

        $product = $lineItem->getProduct();
        if (null === $product) {
            return;
        }

        $productIds = [$product->getId()];
        $parentProduct = $lineItem->getParentProduct();
        if (null !== $parentProduct) {
            $productIds[] = $parentProduct->getId();
        }
        $this->reindexProducts($productIds, $lineItem->getOrder()->getWebsite());
    }

    public function reindexProductOnLineItemUpdate(OrderLineItem $lineItem, PreUpdateEventArgs $event): void
    {
        if (!$this->isReindexAllowed($lineItem)) {
            return;
        }

        $productIds = [];
        $this->collectProductsToReindex($event, self::PRODUCT_FIELD, $productIds);
        $this->collectProductsToReindex($event, self::PARENT_PRODUCT_FIELD, $productIds);
        if ($productIds) {
            $productIds = array_unique($productIds);
            $this->reindexProducts($productIds, $lineItem->getOrder()->getWebsite());
        }
    }

    private function isReindexAllowed(OrderLineItem $lineItem): bool
    {
        $website = $lineItem->getOrder()->getWebsite();
        if (null === $website) {
            return false;
        }

        if (!$this->isFeaturesEnabled($website)) {
            return false;
        }

        $orderStatus = $lineItem->getOrder()->getInternalStatus();

        return
            null !== $orderStatus
            && \in_array($orderStatus->getId(), $this->statusesProvider->getAvailableStatuses(), true);
    }

    private function collectProductsToReindex(PreUpdateEventArgs $event, string $field, array &$productIds): void
    {
        if (!$event->hasChangedField($field)) {
            return;
        }

        $oldProduct = $event->getOldValue($field);
        if ($oldProduct instanceof Product) {
            $productIds[] = $oldProduct->getId();
        }
        $newProduct = $event->getNewValue($field);
        if ($newProduct instanceof Product) {
            $productIds[] = $newProduct->getId();
        }
    }

    private function reindexProducts(array $productIds, Website $website): void
    {
        $websiteIds = $this->reindexationWebsiteProvider->getReindexationWebsiteIds($website);
        $this->eventDispatcher->dispatch(
            new ReindexationRequestEvent([Product::class], $websiteIds, $productIds, true, ['order']),
            ReindexationRequestEvent::EVENT_NAME
        );
    }
}
