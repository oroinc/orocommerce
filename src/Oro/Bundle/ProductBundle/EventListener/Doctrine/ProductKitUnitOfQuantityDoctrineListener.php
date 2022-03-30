<?php

namespace Oro\Bundle\ProductBundle\EventListener\Doctrine;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Exception\InvalidProductKitItemEmptyProductsException;
use Oro\Bundle\ProductBundle\Exception\InvalidProductKitItemUnitOfQuantityException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Sets product unit and referencedUnitPrecisions for ProductKitItem based on its products.
 */
class ProductKitUnitOfQuantityDoctrineListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    public function prePersist(ProductKitItem $productKitItem, LifecycleEventArgs $event): void
    {
        $productKit = $productKitItem->getProductKit();
        if (!$productKit) {
            throw new \LogicException(sprintf('%s::$productKit was not expected to be empty', ProductKitItem::class));
        }

        $this->setReferencedUnitPrecisions($productKitItem, $productKit);
    }

    public function preUpdate(ProductKitItem $productKitItem, PreUpdateEventArgs $event): void
    {
        $this->prePersist($productKitItem, $event);
    }

    private function setReferencedUnitPrecisions(ProductKitItem $productKitItem, Product $productKit): void
    {
        $products = $productKitItem->getProducts();
        if (!$products->count()) {
            $this->logger->debug(
                'It is not possible to create a ProductKitItem (id: {product_kit_item_id}) '
                . 'that has empty "Products" collection',
                [
                    'product_kit_item_id' => $productKitItem->getId(),
                    'product_kit_item' => $productKitItem,
                ]
            );

            throw new InvalidProductKitItemEmptyProductsException($productKitItem);
        }

        $productUnit = $productKitItem->getProductUnit();
        if (!$productUnit) {
            $productUnit = $productKit->getPrimaryUnitPrecision()->getUnit();

            $this->logger->debug(
                '$productUnit is not specified for ProductKitItem (id: {product_kit_item_id}), '
                . 'trying to use ProductUnit "{product_unit}" from the product kit primary unit precision',
                [
                    'product_kit_item_id' => $productKitItem->getId(),
                    'product_unit' => $productUnit->getCode(),
                    'product_kit_item' => $productKitItem,
                ]
            );
        }

        foreach ($products as $product) {
            $productUnitPrecision = $product->getUnitPrecision($productUnit->getCode());
            if (!$productUnitPrecision) {
                $this->logger->debug(
                    'ProductUnit "{product_unit}" cannot be used in ProductKitItem (id: {product_kit_item_id}) '
                    . 'because it is not present in the unit precisions collection of product (id: {product_id})',
                    [
                        'product_kit_item_id' => $productKitItem->getId(),
                        'product_unit' => $productUnit->getCode(),
                        'product_kit_item' => $productKitItem,
                        'product_id' => $product->getId(),
                    ]
                );

                throw new InvalidProductKitItemUnitOfQuantityException($productKitItem, $productUnit, $product);
            }

            $productKitItem->addReferencedUnitPrecision($productUnitPrecision);
        }

        $productKitItem->setProductUnit($productUnit);
    }
}
