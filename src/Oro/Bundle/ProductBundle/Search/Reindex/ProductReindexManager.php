<?php

namespace Oro\Bundle\ProductBundle\Search\Reindex;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Helps to reindex of products in search engine.
 */
class ProductReindexManager
{
    private EventDispatcherInterface $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function reindexProduct(
        Product $product,
        int $websiteId = null,
        bool $isScheduled = true,
        array $fieldGroups = null
    ): void {
        $this->reindexProducts([$product->getId()], $websiteId, $isScheduled, $fieldGroups);
    }

    public function reindexProducts(
        array $productIds,
        int $websiteId = null,
        bool $isScheduled = true,
        array $fieldGroups = null
    ): void {
        if ($productIds) {
            $this->doReindexProducts($productIds, $websiteId, $isScheduled, $fieldGroups);
        }
    }

    public function reindexAllProducts(
        int $websiteId = null,
        bool $isScheduled = true,
        array $fieldGroups = null
    ): void {
        $this->doReindexProducts([], $websiteId, $isScheduled, $fieldGroups);
    }

    private function doReindexProducts(
        array $productIds,
        ?int $websiteId,
        bool $isScheduled,
        array $fieldGroups = null
    ): void {
        $this->dispatcher->dispatch(
            new ReindexationRequestEvent(
                [Product::class],
                null !== $websiteId ? [$websiteId] : [],
                $productIds,
                $isScheduled,
                $fieldGroups
            ),
            ReindexationRequestEvent::EVENT_NAME
        );
    }
}
