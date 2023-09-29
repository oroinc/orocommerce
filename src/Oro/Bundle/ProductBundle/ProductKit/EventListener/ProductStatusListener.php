<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\ProductKit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\ManagerRegistry;
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
class ProductStatusListener
{
    private ManagerRegistry $registry;
    private ProductKitStatusResolver $statusResolver;
    private ProductKitInventoryStatusResolver $inventoryStatusResolver;

    /** @var Collection<Product> */
    private Collection $products;

    public function __construct(
        ManagerRegistry $registry,
        ProductKitStatusResolver $statusResolver,
        ProductKitInventoryStatusResolver $inventoryStatusResolver
    ) {
        $this->products = new ArrayCollection();

        $this->registry = $registry;
        $this->statusResolver = $statusResolver;
        $this->inventoryStatusResolver = $inventoryStatusResolver;
    }

    public function postPersistProductKitItemProduct(
        ProductKitItemProduct $kitItemProduct,
        PostPersistEventArgs $args
    ): void {
        $this->processProductKitItemProduct($kitItemProduct);
    }

    public function preUpdateProductKitItemProduct(
        ProductKitItemProduct $kitItemProduct,
        PreUpdateEventArgs $args
    ): void {
        $this->processProductKitItemProduct($kitItemProduct);
    }

    public function preRemoveProductKitItemProduct(
        ProductKitItemProduct $kitItemProduct,
        PreRemoveEventArgs $args
    ): void {
        $this->processProductKitItemProduct($kitItemProduct);
    }

    private function processProductKitItemProduct(ProductKitItemProduct $kitItemProduct): void
    {
        $productKit = $kitItemProduct->getKitItem()?->getProductKit();

        if ($productKit && !$this->products->contains($productKit)) {
            $this->products->add($productKit);
        }
    }

    public function postPersistProductKitItem(ProductKitItem $productKitItem, PostPersistEventArgs $args): void
    {
        if (!$productKitItem->isOptional()) {
            $productKit = $productKitItem->getProductKit();

            if ($productKit && !$this->products->contains($productKit)) {
                $this->products->add($productKit);
            }
        }
    }

    public function preUpdateProductKitItem(ProductKitItem $productKitItem, PreUpdateEventArgs $args): void
    {
        if (!$args->hasChangedField('optional')) {
            return;
        }

        $productKit = $productKitItem->getProductKit();

        if ($productKit && !$this->products->contains($productKit)) {
            $this->products->add($productKit);
        }
    }

    public function preRemoveProductKitItem(ProductKitItem $productKitItem, PreRemoveEventArgs $args): void
    {
        if ($productKitItem->isOptional()) {
            return;
        }

        $productKit = $productKitItem->getProductKit();

        if ($productKit && !$this->products->contains($productKit)) {
            $this->products->add($productKit);
        }
    }

    /**
     * Collects all newly created product kits to set correct statuses based on underlying products statuses
     */
    public function postPersistProduct(Product $product, PostPersistEventArgs $args): void
    {
        if (!$product->isKit()) {
            return;
        }

        if (!$this->products->contains($product)) {
            $this->products->add($product);
        }
    }

    /**
     * Collect all product kits if the statuses of underlying products was changed
     */
    public function preUpdateProduct(Product $product, PreUpdateEventArgs $args): void
    {
        if (!$product->isSimple()) {
            return;
        }

        if (!$args->hasChangedField('status') && !$args->hasChangedField('inventory_status')) {
            return;
        }

        $repository = $this->registry->getRepository(Product::class);
        $productKits = $repository->getProductKitsByRequiredProduct($product);

        foreach ($productKits as $productKit) {
            if (!$this->products->contains($productKit)) {
                $this->products->add($productKit);
            }
        }
    }

    /**
     * Collect all product kits if the underlying products will be removed
     */
    public function preRemoveProduct(Product $product): void
    {
        if (!$product->isSimple()) {
            return;
        }

        $repository = $this->registry->getRepository(Product::class);
        $productKits = $repository->getProductKitsByRequiredProduct($product);

        foreach ($productKits as $productKit) {
            if (!$this->products->contains($productKit)) {
                $this->products->add($productKit);
            }
        }
    }

    public function onClear(): void
    {
        $this->products->clear();
    }

    /**
     * Process all collected entities
     */
    public function postFlush(): void
    {
        if (!$this->products->isEmpty()) {
            $products = $this->products->toArray();
            $this->statusResolver->resolve(...$products);
            $this->inventoryStatusResolver->resolve(...$products);
            $this->products->clear();
            $this->registry->getManagerForClass(Product::class)->flush();
        }
    }
}
