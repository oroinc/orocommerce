<?php

namespace Oro\Bundle\ProductBundle\Manager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;

class ProductReindexManager
{
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param Product $product
     * @param int|null $websiteId
     */
    public function reindexProduct(Product $product, $websiteId = null)
    {
        $productId = $product->getId();
        $this->triggerReindexationRequestEvent([$productId], $websiteId);
    }

    /**
     * @param array $productIds
     * @param int|null $websiteId
     * @param bool $isScheduled
     */
    public function triggerReindexationRequestEvent(array $productIds, $websiteId = null, $isScheduled = true)
    {
        if ($productIds) {
            $websiteId = is_null($websiteId) ? [] : [$websiteId];
            $event = new ReindexationRequestEvent([Product::class], $websiteId, $productIds, $isScheduled);
            $this->dispatcher->dispatch(ReindexationRequestEvent::EVENT_NAME, $event);
        }
    }
}
