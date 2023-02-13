<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Event\ProductImageResizeEvent;
use Oro\Bundle\ProductBundle\Helper\ProductImageHelper;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Handles assign images to product and ensures that maximum number of images per type is not exceeded.
 */
class ProductImageListener
{
    /**
     * @var int[]
     */
    protected $updatedProductImageIds = [];

    /**
     * @var int[]
     */
    protected $productIdsToReindex = [];

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

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        ImageTypeProvider $imageTypeProvider,
        ProductImageHelper $productImageHelper
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->imageTypeProvider = $imageTypeProvider;
        $this->productImageHelper = $productImageHelper;
    }

    public function postPersist(ProductImage $newProductImage, LifecycleEventArgs $args)
    {
        $entityManager = $args->getObjectManager();
        $product = $newProductImage->getProduct();
        $productImages = $product->getImages();
        $imagesByTypeCounter = $this->productImageHelper->countImagesByType($productImages);

        $currentTypes = array_map(
            static function (ProductImageType $productImageType) {
                return $productImageType->getType();
            },
            $newProductImage->getTypes()->toArray()
        );

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
                if (!\in_array($typeName, $currentTypes, true)) {
                    continue;
                }

                $maxNumberByType = $this->getMaxNumberByType($typeName);
                if ($maxNumberByType === null || $imagesByTypeCounter[$typeName] <= $maxNumberByType) {
                    continue;
                }

                $entityManager->remove($type);
                $productImage->getTypes()->removeElement($type);

                $imagesByTypeCounter[$typeName]--;
            }
        }

        $this->dispatchEvent($newProductImage);
    }

    private function getMaxNumberByType(string $typeName): ?int
    {
        $maxNumberByType = $this->imageTypeProvider->getMaxNumberByType();

        return $maxNumberByType[$typeName]['max'] ?? null;
    }

    public function postUpdate(ProductImage $productImage, LifecycleEventArgs $args)
    {
        if (!in_array($productImage->getId(), $this->updatedProductImageIds)) {
            $this->dispatchEvent($productImage);
            $this->updatedProductImageIds[] = $productImage->getId();
        }
    }

    public function filePostUpdate(File $file, LifecycleEventArgs $args)
    {
        /** @var ProductImage $productImage */
        $productImage = $args->getObjectManager()->getRepository(ProductImage::class)->findOneBy(['image' => $file]);
        if ($productImage && !in_array($productImage->getId(), $this->updatedProductImageIds)) {
            $this->dispatchEvent($productImage);
            $this->updatedProductImageIds[] = $productImage->getId();
        }
    }

    protected function dispatchEvent(ProductImage $productImage)
    {
        if ($productImage->getTypes()->isEmpty()) {
            return;
        }

        $product = $productImage->getProduct();
        if ($product) {
            $productId = $product->getId();
            $this->productIdsToReindex[$productId] = $productId;
        }

        if ($productImage->getImage()?->getExternalUrl() !== null) {
            return;
        }

        $this->eventDispatcher->dispatch(
            new ProductImageResizeEvent($productImage->getId(), true),
            ProductImageResizeEvent::NAME
        );
    }

    public function postFlush(PostFlushEventArgs $event)
    {
        if ($this->productIdsToReindex) {
            $this->eventDispatcher->dispatch(
                new ReindexationRequestEvent(
                    [Product::class],
                    [],
                    $this->productIdsToReindex,
                    true,
                    ['image']
                ),
                ReindexationRequestEvent::EVENT_NAME
            );
        }

        $this->updatedProductImageIds = [];
        $this->productIdsToReindex = [];
    }

    public function onClear(OnClearEventArgs $event)
    {
        if (!$event->getEntityClass() || $event->getEntityClass() === ProductImage::class) {
            $this->updatedProductImageIds = [];
            $this->productIdsToReindex = [];
        }
    }
}
