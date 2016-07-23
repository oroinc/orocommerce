<?php

namespace OroB2B\Bundle\ProductBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use OroB2B\Bundle\ProductBundle\Entity\ProductImage;
use OroB2B\Bundle\ProductBundle\Event\ProductImageResizeEvent;

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
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $this->resizeProductImage($args);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->resizeProductImage($args);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    protected function resizeProductImage(LifecycleEventArgs $args)
    {
        /** @var ProductImage $productImage */
        $productImage = $args->getEntity();
        if (!$productImage instanceof ProductImage) {
            return;
        }

        $this->eventDispatcher->dispatch(
            ProductImageResizeEvent::NAME,
            new ProductImageResizeEvent($productImage)
        );
    }
}
