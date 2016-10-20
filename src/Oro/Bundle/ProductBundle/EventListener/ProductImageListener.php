<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Event\ProductImageResizeEvent;

class ProductImageListener
{
    /**
     * @var int[]
     */
    protected $updatedProductImageIds = [];

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
    public function postPersist(ProductImage $productImage, LifecycleEventArgs $args)
    {
        $this->dispatchEvent($productImage);
    }

    /**
     * @param ProductImage $productImage
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(ProductImage $productImage, LifecycleEventArgs $args)
    {
        if (!in_array($productImage->getId(), $this->updatedProductImageIds)) {
            $this->dispatchEvent($productImage);
            $this->updatedProductImageIds[] = $productImage->getId();
        }
    }

    /**
     * @param File $file
     * @param LifecycleEventArgs $args
     */
    public function filePostUpdate(File $file, LifecycleEventArgs $args)
    {
        /** @var ProductImage $productImage */
        $productImage = $args->getEntityManager()->getRepository(ProductImage::class)->findOneBy(['image' => $file]);
        if ($productImage && !in_array($productImage->getId(), $this->updatedProductImageIds)) {
            $this->dispatchEvent($productImage);
            $this->updatedProductImageIds[] = $productImage->getId();
        }
    }

    /**
     * @param ProductImage $productImage
     */
    protected function dispatchEvent(ProductImage $productImage)
    {
        if (!$productImage->getTypes()) {
            return;
        }

        $this->eventDispatcher->dispatch(
            ProductImageResizeEvent::NAME,
            new ProductImageResizeEvent($productImage, $forceOption = true)
        );
    }
}
