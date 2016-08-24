<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductImageRepository;
use Oro\Bundle\ProductBundle\Event\ProductImageResizeEvent;

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
     * @var string
     */
    private $productImageClass;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param string $productImageClass
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, $productImageClass)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->productImageClass = $productImageClass;
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
            /** @var ProductImageRepository $productImageRepository */
            $productImageRepository = $args->getEntityManager()->getRepository($this->productImageClass);
            /** @var ProductImage $productImage */
            $productImage = $productImageRepository->findOneByImage($file);
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
            $this->eventDispatcher->dispatch(
                ProductImageResizeEvent::NAME,
                new ProductImageResizeEvent($data['productImage'], $data['forceOption'])
            );
        }
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
