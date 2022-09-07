<?php

namespace Oro\Bundle\ProductBundle\Search\Reindex;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * This service help prepare and make event dispatching to make reindex of products data in search engine.
 */
class ProductReindexManager
{
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param Product $product
     * @param int|null $websiteId
     * @param bool $isScheduled
     */
    public function reindexProduct(Product $product, $websiteId = null, $isScheduled = true)
    {
        $this->reindexProductWithFieldGroups($product, $websiteId, $isScheduled);
    }

    public function reindexProductWithFieldGroups(
        Product $product,
        int $websiteId = null,
        bool $isScheduled = true,
        array $fieldGroups = null
    ): void {
        $productId = $product->getId();
        $this->reindexProductsWithFieldGroups([$productId], $websiteId, $isScheduled, $fieldGroups);
    }

    /**
     * @param array $productIds
     * @param int|null $websiteId
     * @param bool $isScheduled
     * @return void
     */
    public function reindexProducts(array $productIds, $websiteId = null, $isScheduled = true)
    {
        $this->reindexProductsWithFieldGroups($productIds, $websiteId, $isScheduled);
    }

    public function reindexProductsWithFieldGroups(
        array $productIds,
        int $websiteId = null,
        bool $isScheduled = true,
        array $fieldGroups = null
    ): void {
        if ($productIds) {
            $this->doReindexProductsWithFieldGroups($productIds, $websiteId, $isScheduled, $fieldGroups);
        }
    }

    /**
     * @param null $websiteId
     * @param bool $isScheduled
     */
    public function reindexAllProducts($websiteId = null, $isScheduled = true)
    {
        $this->reindexAllProductsWithFieldGroups($websiteId, $isScheduled);
    }

    public function reindexAllProductsWithFieldGroups(
        int $websiteId = null,
        bool $isScheduled = true,
        array $fieldGroups = null
    ): void {
        $this->doReindexProductsWithFieldGroups([], $websiteId, $isScheduled, $fieldGroups);
    }

    /**
     * @param array $productIds
     * @param int|null $websiteId
     * @param bool $isScheduled
     *
     * @return ReindexationRequestEvent
     */
    protected function getReindexationRequestEvent(array $productIds, $websiteId, $isScheduled)
    {
        return $this->getReindexationRequestEventWithFieldGroups($productIds, $websiteId, (bool)$isScheduled);
    }

    protected function getReindexationRequestEventWithFieldGroups(
        array $productIds,
        ?int $websiteId,
        bool $isScheduled,
        array $fieldGroups = null
    ): ReindexationRequestEvent {
        return new ReindexationRequestEvent(
            [Product::class],
            null === $websiteId ? [] : [$websiteId],
            $productIds,
            $isScheduled,
            $fieldGroups
        );
    }

    /**
     * @param array $productIds
     * @param int|null $websiteId
     * @param bool $isScheduled
     */
    protected function doReindexProducts(array $productIds, $websiteId, $isScheduled)
    {
        $this->doReindexProductsWithFieldGroups($productIds, $websiteId, (bool)$isScheduled);
    }

    protected function doReindexProductsWithFieldGroups(
        array $productIds,
        ?int $websiteId,
        bool $isScheduled,
        array $fieldGroups = null
    ): void {
        $event = $this->getReindexationRequestEventWithFieldGroups($productIds, $websiteId, $isScheduled, $fieldGroups);
        $this->dispatcher->dispatch($event, ReindexationRequestEvent::EVENT_NAME);
    }
}
