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
        if (!$productImage->getTypes()) {
            return;
        }

        $this->eventDispatcher->dispatch(
            ProductImageResizeEvent::NAME,
            new ProductImageResizeEvent($productImage, $forceOption = true)
        );
    }

    /**
     * @param ProductImage $productImage
     * @param LifecycleEventArgs $args
     */
    public function postPersist(ProductImage $productImage, LifecycleEventArgs $args)
    {
        $this->postUpdate($productImage, $args);
    }

    /**
     * @param ProductImage $productImage
     * @param PreFlushEventArgs $args
     */
    public function preFlush(ProductImage $productImage, PreFlushEventArgs $args)
    {
        //if new file uploaded -> trigger update
        if ($productImage->getId() && $productImage->getImage() && $productImage->getImage()->getFile()) {
            $productImage->setUpdatedAt();
        }
    }
}
