<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Event\ProductImageResizeEvent;

class ProductImageListener
{
    /**
     * @var int[]
     */
    protected $processedImageIds = [];

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param ProductImage $productImage
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(ProductImage $productImage, LifecycleEventArgs $args)
    {
        $this->dispatchEvent($productImage);
    }

    /**
     * @param ProductImage $productImage
     * @param LifecycleEventArgs $args
     */
    public function postPersist(ProductImage $productImage, LifecycleEventArgs $args)
    {
        $this->dispatchEvent($productImage);
    }

    /**
     * @param ProductImage $productImage
     * @param PreFlushEventArgs $args
     */
    public function preFlush(ProductImage $productImage, PreFlushEventArgs $args)
    {
        if ($productImage->getId() && $productImage->hasUploadedFile()) {
            $productImage->setUpdatedAtToNow();
        }
    }

    /**
     * @param ProductImage $productImage
     */
    private function dispatchEvent(ProductImage $productImage)
    {
        if (!$productImage->getTypes() || in_array($productImage->getId(), $this->processedImageIds)) {
            return;
        }

        $this->processedImageIds[] = $productImage->getId();

        $this->eventDispatcher->dispatch(
            ProductImageResizeEvent::NAME,
            new ProductImageResizeEvent($productImage, $forceOption = true)
        );
    }
}
