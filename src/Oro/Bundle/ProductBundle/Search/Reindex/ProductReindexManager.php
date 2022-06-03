<?php

namespace Oro\Bundle\ProductBundle\Search\Reindex;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * This service help to prepare and make event dispatching to make reindex of products data in search engine.
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
    public function reindexProduct(Product $product, $websiteId = null, $isScheduled = true, array $fieldGroups = null)
    {
        $productId = $product->getId();
        $this->reindexProducts([$productId], $websiteId, $isScheduled, $fieldGroups);
    }

    /**
     * @param array $productIds
     * @param int|null $websiteId
     * @param bool $isScheduled
     */
    public function reindexProducts(
        array $productIds,
        $websiteId = null,
        $isScheduled = true,
        array $fieldGroups = null
    ) {
        if ($productIds) {
            $this->doReindexProducts($productIds, $websiteId, $isScheduled, $fieldGroups);
        }
    }

    /**
     * @param null $websiteId
     * @param bool $isScheduled
     */
    public function reindexAllProducts($websiteId = null, $isScheduled = true, array $fieldGroups = null)
    {
        $this->doReindexProducts([], $websiteId, $isScheduled, $fieldGroups);
    }

    /**
     * @param array $productIds
     * @param int|null $websiteId
     * @param bool $isScheduled
     *
     * @return ReindexationRequestEvent
     */
    protected function getReindexationRequestEvent(
        array $productIds,
        $websiteId,
        $isScheduled,
        array $fieldGroups = null
    ) {
        $websiteId = is_null($websiteId) ? [] : [$websiteId];

        return new ReindexationRequestEvent([Product::class], $websiteId, $productIds, $isScheduled, $fieldGroups);
    }

    /**
     * @param array $productIds
     * @param int|null $websiteId
     * @param bool $isScheduled
     */
    protected function doReindexProducts(array $productIds, $websiteId, $isScheduled, array $fieldGroups = null)
    {
        $event = $this->getReindexationRequestEvent($productIds, $websiteId, $isScheduled, $fieldGroups);
        $this->dispatcher->dispatch($event, ReindexationRequestEvent::EVENT_NAME);
    }
}
