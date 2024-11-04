<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\ProductKit\EventListener;

use Doctrine\ORM\Event\OnClearEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\ProductKit\Resolver\ProductKitInventoryStatusResolver;
use Oro\Bundle\ProductBundle\ProductKit\Resolver\ProductKitStatusResolver;

/**
 * This listener aims to set correct statuses to the product kits
 * when underlying product status or inventory status is changed
 * or underlying product being removed from the product kit
 *
 * Note: only underlying simple products that belongs to non-optional product kit items
 * may affect statuses of product kit
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class StatusListener
{
    private ProductKitStatusResolver $statusResolver;
    private ProductKitInventoryStatusResolver $inventoryStatusResolver;

    /** @var array<string,array<string,Product>> */
    private array $products;

    public function __construct(
        ProductKitStatusResolver $statusResolver,
        ProductKitInventoryStatusResolver $inventoryStatusResolver
    ) {
        $this->products = [];

        $this->statusResolver = $statusResolver;
        $this->inventoryStatusResolver = $inventoryStatusResolver;
    }

    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $objectManager = $eventArgs->getObjectManager();
        $unitOfWork = $objectManager->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            $this->processEntityInsert($objectManager, $entity);
        }

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            $this->processEntityUpdate($objectManager, $entity);
        }

        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            $this->processEntityDelete($objectManager, $entity);
        }
    }

    private function processEntityInsert(ObjectManager $objectManager, object $entity): void
    {
        if ($entity instanceof ProductKitItemProduct) {
            $this->processProductKitItemProduct($objectManager, $entity);
        } elseif ($entity instanceof ProductKitItem) {
            if (!$entity->isOptional()) {
                $this->addProduct($objectManager, $entity->getProductKit());
            }
        } elseif ($entity instanceof Product) {
            if ($entity->isKit()) {
                // Collects new product kit to set correct status based on underlying products statuses.
                $this->addProduct($objectManager, $entity);
            }
        }
    }

    private function processEntityUpdate(ObjectManager $objectManager, object $entity): void
    {
        $unitOfWork = $objectManager->getUnitOfWork();

        if ($entity instanceof ProductKitItemProduct) {
            $this->processProductKitItemProduct($objectManager, $entity);
        } elseif ($entity instanceof ProductKitItem) {
            $changeSet = $unitOfWork->getEntityChangeSet($entity);
            if (isset($changeSet['optional'])) {
                $this->addProduct($objectManager, $entity->getProductKit());
            }
        } elseif ($entity instanceof Product) {
            if ($entity->isSimple()) {
                $changeSet = $unitOfWork->getEntityChangeSet($entity);
                if (isset($changeSet['status']) || isset($changeSet['serialized_data'][0]['inventory_status'])) {
                    $this->processSimpleProduct($objectManager, $entity);
                }
            }
        }
    }

    private function processEntityDelete(ObjectManager $objectManager, object $entity): void
    {
        if ($entity instanceof ProductKitItemProduct) {
            $this->processProductKitItemProduct($objectManager, $entity);
        } elseif ($entity instanceof ProductKitItem) {
            if (!$entity->isOptional()) {
                $this->addProduct($objectManager, $entity->getProductKit());
            }
        } elseif ($entity instanceof Product) {
            if ($entity->isSimple()) {
                $this->processSimpleProduct($objectManager, $entity);
            }
        }
    }

    /**
     * Collects related product kits of the given product kit item product.
     */
    private function processProductKitItemProduct(
        ObjectManager $objectManager,
        ProductKitItemProduct $kitItemProduct
    ): void {
        $productKit = $kitItemProduct->getKitItem()?->getProductKit();

        if ($productKit) {
            $this->addProduct($objectManager, $productKit);
        }
    }

    /**
     * Collects related product kits of the given simple product.
     */
    private function processSimpleProduct(ObjectManager $objectManager, Product $product): void
    {
        $repository = $objectManager->getRepository(Product::class);
        $productKits = $repository->getProductKitsByRequiredProduct($product);
        foreach ($productKits as $productKit) {
            $this->addProduct($objectManager, $productKit);
        }
    }

    private function addProduct(ObjectManager $objectManager, Product $product): void
    {
        $objectManagerHash = spl_object_hash($objectManager);
        $productHash = spl_object_hash($product);
        $this->products[$objectManagerHash][$productHash] = $product;
    }

    /**
     * Process all collected entities
     */
    public function postFlush(PostFlushEventArgs $eventArgs): void
    {
        $objectManager = $eventArgs->getObjectManager();
        $objectManagerHash = spl_object_hash($objectManager);

        if (!empty($this->products[$objectManagerHash])) {
            $this->statusResolver->resolve(...$this->products[$objectManagerHash]);
            $this->inventoryStatusResolver->resolve(...$this->products[$objectManagerHash]);
            $this->products[$objectManagerHash] = [];

            $objectManager->flush();
        }
    }

    public function onClear(OnClearEventArgs $eventArgs): void
    {
        $objectManagerHash = spl_object_hash($eventArgs->getObjectManager());

        $this->products[$objectManagerHash] = [];
    }
}
