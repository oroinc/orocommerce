<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\ProductKit\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\ProductKit\Checker\ProductKitItemAvailabilityChecker;
use Oro\Bundle\ProductBundle\ProductKit\Provider\ProductKitItemsProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductKitItemsProviderTest extends TestCase
{
    private ProductKitItemAvailabilityChecker|MockObject $kitItemAvailabilityChecker;

    private ProductKitItemsProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->kitItemAvailabilityChecker = $this->createMock(ProductKitItemAvailabilityChecker::class);

        $this->provider = new ProductKitItemsProvider($this->kitItemAvailabilityChecker);
    }

    public function testGetKitItemsAvailableForPurchaseWhenNoKitItemProducts(): void
    {
        self::assertEquals([], $this->provider->getAvailableKitItems(new Product()));
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
            ->method('isAvailable')
            ->withConsecutive([$kitItem1], [$kitItem2])
            ->willReturnOnConsecutiveCalls(true, false);

        self::assertEquals([$kitItem1], $this->provider->getAvailableKitItems($product));
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
            ->method('isAvailable')
            ->withConsecutive([$kitItem1], [$kitItem2])
            ->willReturnOnConsecutiveCalls(false, false);

        self::assertEquals([], $this->provider->getAvailableKitItems($product));
    }
}
