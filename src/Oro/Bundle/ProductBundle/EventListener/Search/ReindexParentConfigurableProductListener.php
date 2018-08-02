<?php

namespace Oro\Bundle\ProductBundle\EventListener\Search;

use Doctrine\ORM\Event\OnClearEventArgs;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Reindex parent configurable product when one of the product variants has changed
 */
class ReindexParentConfigurableProductListener
{
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var array */
    protected $productIds = [];

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param Product $product
     */
    public function postPersist(Product $product)
    {
        $this->populateProductIds($product);
    }

    /**
     * @param Product $product
     */
    public function postUpdate(Product $product)
    {
        $this->populateProductIds($product);
    }

    /**
     * @param Product $product
     */
    public function preRemove(Product $product)
    {
        $this->populateProductIds($product);
    }

    public function postFlush()
    {
        if ($this->productIds) {
            $this->eventDispatcher->dispatch(
                ReindexationRequestEvent::EVENT_NAME,
                new ReindexationRequestEvent([Product::class], [], array_unique($this->productIds))
            );
            $this->productIds = [];
        }
    }

    /**
     * @param OnClearEventArgs $event
     */
    public function onClear(OnClearEventArgs $event)
    {
        if (!$event->getEntityClass() || $event->getEntityClass() === Product::class) {
            $this->productIds = [];
        }
    }

    /**
     * @param Product $product
     */
    protected function populateProductIds(Product $product)
    {
        if ($product->isVariant()) {
            foreach ($product->getParentVariantLinks() as $parentVariantLink) {
                $this->productIds[] = $parentVariantLink->getParentProduct()->getId();
            }
        }
    }
}
