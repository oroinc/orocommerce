<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Form\Type\ProductKitItemLineItemType;
use Oro\Bundle\ShoppingListBundle\ProductKit\Provider\ProductKitItemProductsProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\DataTransformerInterface;

trait ProductKitItemLineItemTypeTrait
{
    protected function createProductKitItemLineItemType(TestCase $testCase, array $products): ProductKitItemLineItemType
    {
        $kitItemProductsProvider = $testCase->createMock(ProductKitItemProductsProvider::class);
        $kitItemProductsProvider
            ->method('getProductsAvailableForPurchase')
            ->willReturn($products);

        $productToIdDataTransformer = $testCase->createMock(DataTransformerInterface::class);
        $productToIdDataTransformer
            ->method('transform')
            ->willReturnCallback(static fn (?Product $product) => $product?->getId());

        $productToIdDataTransformer
            ->method('reverseTransform')
            ->willReturnCallback(static function (?int $id) use ($products) {
                foreach ($products as $product) {
                    if ($product->getId() === $id) {
                        return $product;
                    }
                }

                return null;
            });

        return new ProductKitItemLineItemType($kitItemProductsProvider, $productToIdDataTransformer);
    }
}
