<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
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
     * @param ImageTypeProvider $imageTypeProvider
     * @param ProductImageHelper $productImageHelper
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
     * @param ProductImage $newProductImage
     * @param LifecycleEventArgs $args
     */
    public function postPersist(ProductImage $newProductImage, LifecycleEventArgs $args)
    {
        $entityManager = $args->getEntityManager();
        $product = $newProductImage->getProduct();
        $productImages = $product->getImages();
        $imagesByTypeCounter = $this->productImageHelper->countImagesByType($productImages);

        // Ensures that maximum number of images per type is not exceeded.
        // Required for API and UI import.
        foreach ($productImages as $productImage) {
            if ($newProductImage === $productImage) {
                // Skips new product image because its types have higher priority - we shouldn't remove them.
                continue;
            }

            // Goes through types, removes a type if maximum number of images is exceeded.
            foreach ($productImage->getTypes() as $type) {
                $typeName = $type->getType();
                $maxNumberByType = $this->getMaxNumberByType($typeName);
                if ($maxNumberByType === null || $imagesByTypeCounter[$typeName] <= $maxNumberByType) {
                    continue;
                }

                $entityManager->remove($type);
                $productImage->getTypes()->removeElement($type);
            }
        }

        $this->dispatchEvent($newProductImage);
    }

    /**
     * @param string $typeName
     *
     * @return int|null
     */
    private function getMaxNumberByType(string $typeName): ?int
    {
        $maxNumberByType = $this->imageTypeProvider->getMaxNumberByType();

        return $maxNumberByType[$typeName]['max'] ?? null;
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

        $product = $productImage->getProduct();
        if ($product) {
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
