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
     * @param Product  $product
     * @param int|null $websiteId
     * @param bool     $isScheduled
     */
    public function reindexProduct(Product $product, $websiteId = null, $isScheduled = true)
    {
        $productId = $product->getId();
        $this->reindexProducts([$productId], $websiteId, $isScheduled);
    }

    /**
     * @param array    $productIds
     * @param int|null $websiteId
     * @param bool     $isScheduled
     */
    public function reindexProducts(array $productIds, $websiteId = null, $isScheduled = true)
    {
        if ($productIds) {
            $this->doReindexProducts($productIds, $websiteId, $isScheduled);
        }
    }

    /**
     * @param null $websiteId
     * @param bool $isScheduled
     */
    public function reindexAllProducts($websiteId = null, $isScheduled = true)
    {
        $this->doReindexProducts([], $websiteId, $isScheduled);
    }

    /**
     * @param array    $productIds
     * @param int|null $websiteId
     * @param bool     $isScheduled
     *
     * @return ReindexationRequestEvent
     */
    protected function getReindexationRequestEvent(array $productIds, $websiteId, $isScheduled)
    {
        $websiteId = is_null($websiteId) ? [] : [$websiteId];
        return new ReindexationRequestEvent([Product::class], $websiteId, $productIds, $isScheduled);
    }

    /**
     * @param array    $productIds
     * @param int|null $websiteId
     * @param bool     $isScheduled
     */
    protected function doReindexProducts(array $productIds, $websiteId, $isScheduled)
    {
        $event = $this->getReindexationRequestEvent($productIds, $websiteId, $isScheduled);
        $this->dispatcher->dispatch($event, ReindexationRequestEvent::EVENT_NAME);
    }
}
