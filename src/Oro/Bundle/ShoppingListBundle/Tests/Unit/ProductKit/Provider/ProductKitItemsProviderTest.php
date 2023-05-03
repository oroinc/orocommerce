<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\ProductKit\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ShoppingListBundle\ProductKit\Checker\ProductKitItemAvailabilityChecker;
use Oro\Bundle\ShoppingListBundle\ProductKit\Provider\ProductKitItemsProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductKitItemsProviderTest extends TestCase
{
    private ProductKitItemAvailabilityChecker|MockObject $kitItemAvailabilityChecker;

    private ProductKitItemsProvider $provider;

    protected function setUp(): void
    {
        $this->kitItemAvailabilityChecker = $this->createMock(ProductKitItemAvailabilityChecker::class);

        $this->provider = new ProductKitItemsProvider($this->kitItemAvailabilityChecker);
    }

    public function testGetKitItemsAvailableForPurchaseWhenNoKitItemProducts(): void
    {
        self::assertEquals([], $this->provider->getKitItemsAvailableForPurchase(new Product()));
    }

    public function testGetKitItemsAvailableForPurchaseWhenHasKitItemProducts(): void
    {
        $product = (new ProductStub())->setId(11);
        $kitItem1 = (new ProductKitItem())->setProductKit($product);
        $kitItem2 = (new ProductKitItem())->setProductKit($product);
        $product
            ->addKitItem($kitItem1)
            ->addKitItem($kitItem2);
        $this->kitItemAvailabilityChecker
            ->expects(self::exactly(2))
            ->method('isAvailableForPurchase')
            ->withConsecutive([$kitItem1], [$kitItem2])
            ->willReturnOnConsecutiveCalls(true, false);

        self::assertEquals([$kitItem1], $this->provider->getKitItemsAvailableForPurchase($product));
    }

    public function testGetKitItemsAvailableForPurchaseWhenHasKitItemProductsButNoAvailable(): void
    {
        $product = (new ProductStub())->setId(11);
        $kitItem1 = (new ProductKitItem())->setProductKit($product);
        $kitItem2 = (new ProductKitItem())->setProductKit($product);
        $product
            ->addKitItem($kitItem1)
            ->addKitItem($kitItem2);

        $this->kitItemAvailabilityChecker
            ->expects(self::exactly(2))
            ->method('isAvailableForPurchase')
            ->withConsecutive([$kitItem1], [$kitItem2])
            ->willReturnOnConsecutiveCalls(false, false);

        self::assertEquals([], $this->provider->getKitItemsAvailableForPurchase($product));
    }
}
