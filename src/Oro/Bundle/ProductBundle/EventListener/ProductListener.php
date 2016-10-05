<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\ProductImageResizeEvent;

class ProductListener
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
     * @param Product $product
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(Product $product, LifecycleEventArgs $args)
    {
        foreach ($product->getImages() as $productImage) {
            if (!$productImage->getTypes()) {
                continue;
            }

            $imageChangeSet = $args->getEntityManager()->getUnitOfWork()->getEntityChangeSet($productImage);
            $fileChangeSet = $args->getEntityManager()->getUnitOfWork()->getEntityChangeSet($productImage->getImage());

            $imageChanged = !empty($imageChangeSet) || !empty($fileChangeSet);

            if ($imageChanged) {
                $this->eventDispatcher->dispatch(
                    ProductImageResizeEvent::NAME,
                    new ProductImageResizeEvent($productImage, $forceOption = true)
                );
            }
        }
    }
}
