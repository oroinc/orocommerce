<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Oro\Bundle\OrderBundle\Form\Type\EventListener\OrderProductKitItemLineItemDefaultDataListener;
use Oro\Bundle\OrderBundle\Form\Type\EventListener\OrderProductKitItemLineItemGhostOptionListener;
use Oro\Bundle\OrderBundle\Form\Type\OrderProductKitItemLineItemType;
use Oro\Bundle\OrderBundle\ProductKit\Factory\OrderProductKitItemLineItemFactory;
use Oro\Bundle\ProductBundle\ProductKit\Provider\ProductKitItemProductsProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\TestCase;

trait OrderProductKitItemLineItemTypeTrait
{
    protected function createOrderProductKitItemLineItemType(
        TestCase $testCase,
        array $products
    ): OrderProductKitItemLineItemType {
        $kitItemProductsProvider = $testCase->createMock(ProductKitItemProductsProvider::class);
        $kitItemProductsProvider
            ->method('getAvailableProducts')
            ->willReturn($products);

        $kitItemProductsProvider
            ->method('getFirstAvailableProduct')
            ->willReturn(reset($products));

        $kitItemLineItemFactory = new OrderProductKitItemLineItemFactory($kitItemProductsProvider);

        $kitItemLineItemGhostOptionListener = new OrderProductKitItemLineItemGhostOptionListener();
        $kitItemLineItemGhostOptionListener->setGhostOptionClass(ProductStub::class);

        return new OrderProductKitItemLineItemType(
            $kitItemProductsProvider,
            new OrderProductKitItemLineItemDefaultDataListener($kitItemLineItemFactory),
            $kitItemLineItemGhostOptionListener
        );
    }
}
