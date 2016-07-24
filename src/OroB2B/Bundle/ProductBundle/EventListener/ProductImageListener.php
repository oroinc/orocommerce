<?php

namespace OroB2B\Bundle\ProductBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\AttachmentBundle\Entity\File;

use OroB2B\Bundle\ProductBundle\Entity\ProductImage;
use OroB2B\Bundle\ProductBundle\Entity\ProductImageType;
use OroB2B\Bundle\ProductBundle\Event\ProductImageResizeEvent;

class ProductImageListener
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var array
     */
    protected $productImagesForResize = [];

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
        /** @var ProductImageType $entity */
        $productImageType = $args->getEntity();

        if ($productImageType instanceof ProductImageType) {
            $productImage = $productImageType->getProductImage();
            $this->addProductImageForResize($productImage);
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $file = $args->getEntity();
        if ($file instanceof File) {
            /** @var ProductImage $productImage */
            $productImage = $args->getEntityManager()->getRepository(ProductImage::class)->findOneByImage($file);
            if ($productImage && $productImage->getTypes()) {
                $this->addProductImageForResize($productImage, $forceOption = true);
            }
        }
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        foreach ($this->productImagesForResize as $key => $data) {
            unset($this->productImagesForResize[$key]);
            $this->sendProductImageResizeEvent($data['productImage'], $data['forceOption']);
        }
    }

    /**
     * @param ProductImage $productImage
     * @param bool $forceOption
     */
    protected function sendProductImageResizeEvent(ProductImage $productImage, $forceOption)
    {
        $this->eventDispatcher->dispatch(
            ProductImageResizeEvent::NAME,
            new ProductImageResizeEvent($productImage, $forceOption)
        );
    }

    /**
     * @param ProductImage $productImage
     * @param bool $forceOption
     */
    protected function addProductImageForResize(ProductImage $productImage, $forceOption = false)
    {
        $this->productImagesForResize[$productImage->getId()] = [
            'productImage' => $productImage,
            'forceOption' => $forceOption
        ];
    }
}
