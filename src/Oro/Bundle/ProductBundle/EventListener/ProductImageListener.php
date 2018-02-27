<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Event\ProductImageResizeEvent;
use Oro\Bundle\ProductBundle\Helper\ProductImageHelper;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProductImageListener
{
    /**
     * @var int[]
     */
    protected $updatedProductImageIds = [];

    /**
     * @var EventDispatcherInterface $eventDispatcher
     */
    protected $eventDispatcher;

    /**
     * @var ImageTypeProvider $imageTypeProvider
     */
    protected $imageTypeProvider;

    /**
     * @var ProductImageHelper $productImageHelper
     */
    protected $productImageHelper;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        ImageTypeProvider $imageTypeProvider,
        ProductImageHelper $productImageHelper
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->imageTypeProvider = $imageTypeProvider;
        $this->productImageHelper = $productImageHelper;
    }

    /**
     * @param ProductImage $productImage
     * @param LifecycleEventArgs $args
     */
    public function postPersist(ProductImage $productImage, LifecycleEventArgs $args)
    {
        /** @var Product $parentProduct */
        $parentProduct = $productImage->getProduct();

        /** @var Collection $images */
        $parentProductImages = $parentProduct->getImages();

        if ($parentProductImages->contains($productImage)) {
            $parentProductImages->removeElement($productImage);
        }

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $args->getEntityManager();

        //Remove all types of the new image within the existing collection,
        //simulating a replace , required for api and UI import
        foreach ($productImage->getTypes() as $newType) {
            $this->removeImageTypeFromParent($parentProductImages, $newType->getType(), $entityManager);
        }

        $parentProduct->addImage($productImage);

        $this->dispatchEvent($productImage);
    }

    /**
     * Removes existing type from parent product image collection
     *
     * @param Collection $parentProductImages
     * @param string $newTypeName
     * @param EntityManagerInterface $entityManager
     */
    private function removeImageTypeFromParent(
        Collection $parentProductImages,
        string $newTypeName,
        EntityManagerInterface $entityManager
    ) {
        $maxNumberByType = $this->imageTypeProvider->getMaxNumberByType();
        $imagesByTypeCounter = $this->productImageHelper->countImagesByType($parentProductImages);

        /** @var ProductImage $productImage */
        foreach ($parentProductImages as $productImage) {
            /** @var ProductImageType $type */
            foreach ($productImage->getTypes() as $type) {
                $name = $type->getType();
                if ($newTypeName === $name &&
                    !is_null($max = $maxNumberByType[$name]['max']) &&
                    $imagesByTypeCounter[$name] >= $max
                ) {
                    $entityManager->remove($type);
                    $productImage->getTypes()->removeElement($type);
                    break;
                }
            }
        }
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
        if ($productImage->getTypes()->isEmpty()) {
            return;
        }

        if ($product = $productImage->getProduct()) {
            $this->eventDispatcher->dispatch(
                ReindexationRequestEvent::EVENT_NAME,
                new ReindexationRequestEvent(
                    [
                        Product::class],
                    [],
                    [
                        $product->getId()
                    ]
                )
            );
        }

        $this->eventDispatcher->dispatch(
            ProductImageResizeEvent::NAME,
            new ProductImageResizeEvent($productImage->getId(), true)
        );
    }
}
