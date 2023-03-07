<?php

namespace Oro\Bundle\ProductBundle\EventListener\Doctrine;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Exception\InvalidProductKitItemEmptyProductsException;
use Oro\Bundle\ProductBundle\Exception\ProductKitItemEmptyProductUnitException;
use Oro\Bundle\ProductBundle\Exception\ProductKitItemInvalidProductUnitException;
use Oro\Bundle\ProductBundle\Service\ProductKitItemProductUnitChecker;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Sets product unit and referencedUnitPrecisions for ProductKitItem based on its products.
 */
class ProductKitUnitOfQuantityDoctrineListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ProductKitItemProductUnitChecker $productUnitChecker;

    public function __construct(ProductKitItemProductUnitChecker $productUnitChecker)
    {
        $this->productUnitChecker = $productUnitChecker;
        $this->logger = new NullLogger();
    }

    public function prePersist(ProductKitItem $productKitItem, LifecycleEventArgs $event): void
    {
        $productKit = $productKitItem->getProductKit();
        if (!$productKit) {
            throw new \LogicException('ProductKitItem::$productKit was not expected to be empty');
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
                'It is not possible to create a ProductKitItem with empty kitItemProducts collection',
                ['product_kit_item' => $productKitItem]
            );

            throw new InvalidProductKitItemEmptyProductsException($productKitItem);
        }

        $productUnit = $productKitItem->getProductUnit();
        if (!$productUnit) {
            $this->logger->debug(
                'It is not possible to create a ProductKitItem that has empty productUnit',
                ['product_kit_item' => $productKitItem]
            );

            throw new ProductKitItemEmptyProductUnitException($productKitItem);
        }

        $unitCode = $productUnit->getCode();
        $unitPrecisions = $this->productUnitChecker->getEligibleProductUnitPrecisions($unitCode, $products);

        if (count($unitPrecisions) !== count($products)) {
            $this->logger->debug(
                'Product unit "{product_unit}" cannot be used in ProductKitItem'
                . ' because it is not present in each product unit precisions collection of the ProductKitItem'
                . ' $products collection',
                [
                    'product_kit_item' => $productKitItem,
                    'product_unit' => $unitCode,
                ]
            );

            throw new ProductKitItemInvalidProductUnitException($productKitItem, $productUnit);
        }

        $productKitItem->setReferencedUnitPrecisions($unitPrecisions);
    }
}
