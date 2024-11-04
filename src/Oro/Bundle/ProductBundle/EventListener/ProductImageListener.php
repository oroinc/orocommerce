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
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Handles assign images to product and ensures that maximum number of images per type is not exceeded.
 */
class ProductImageListener implements ServiceSubscriberInterface
{
    private EventDispatcherInterface $eventDispatcher;
    private ContainerInterface $container;
    /** @var int[] */
    private $updatedProductImageIds = [];
    /** @var int[] */
    private $productIdsToReindex = [];

    public function __construct(EventDispatcherInterface $eventDispatcher, ContainerInterface $container)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->container = $container;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ImageTypeProvider::class,
            ProductImageHelper::class
        ];
    }

    public function postPersist(ProductImage $newProductImage, LifecycleEventArgs $args): void
    {
        $entityManager = $args->getObjectManager();
        $product = $newProductImage->getProduct();
        $productImages = $product->getImages();
        $imagesByTypeCounter = $this->getProductImageHelper()->countImagesByType($productImages);

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

    public function postUpdate(ProductImage $productImage, LifecycleEventArgs $args): void
    {
        if (!in_array($productImage->getId(), $this->updatedProductImageIds)) {
            $this->dispatchEvent($productImage);
            $this->updatedProductImageIds[] = $productImage->getId();
        }
    }

    public function filePostUpdate(File $file, LifecycleEventArgs $args): void
    {
        /** @var ProductImage $productImage */
        $productImage = $args->getObjectManager()->getRepository(ProductImage::class)->findOneBy(['image' => $file]);
        if ($productImage && !in_array($productImage->getId(), $this->updatedProductImageIds)) {
            $this->dispatchEvent($productImage);
            $this->updatedProductImageIds[] = $productImage->getId();
        }
    }

    public function postFlush(PostFlushEventArgs $event): void
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

    public function onClear(OnClearEventArgs $event): void
    {
        if (!$event->getEntityClass() || $event->getEntityClass() === ProductImage::class) {
            $this->updatedProductImageIds = [];
            $this->productIdsToReindex = [];
        }
    }

    private function getMaxNumberByType(string $typeName): ?int
    {
        $maxNumberByType = $this->getImageTypeProvider()->getMaxNumberByType();

        return $maxNumberByType[$typeName]['max'] ?? null;
    }

    private function dispatchEvent(ProductImage $productImage): void
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

    private function getImageTypeProvider(): ImageTypeProvider
    {
        return $this->container->get(ImageTypeProvider::class);
    }

    private function getProductImageHelper(): ProductImageHelper
    {
        return $this->container->get(ProductImageHelper::class);
    }
}
