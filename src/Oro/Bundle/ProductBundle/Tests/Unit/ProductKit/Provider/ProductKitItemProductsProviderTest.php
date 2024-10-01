<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\ProductKit\Provider;

use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\ProductKit\Checker\ProductKitItemProductAvailabilityChecker;
use Oro\Bundle\ProductBundle\ProductKit\Provider\ProductKitItemProductsProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductKitItemProductsProviderTest extends TestCase
{
    private ProductKitItemProductAvailabilityChecker|MockObject $kitItemProductAvailabilityChecker;

    private ProductKitItemProductsProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->kitItemProductAvailabilityChecker = $this->createMock(ProductKitItemProductAvailabilityChecker::class);

        $this->provider = new ProductKitItemProductsProvider($this->kitItemProductAvailabilityChecker);
    }

    public function testGetProductsAvailableForPurchaseWhenNoKitItemProducts(): void
    {
        self::assertEquals([], $this->provider->getAvailableProducts(new ProductKitItem()));
    }

    public function testGetProductsAvailableForPurchaseWhenHasKitItemProducts(): void
    {
        $product1 = (new ProductStub())->setId(11);
        $kitItemProduct1 = (new ProductKitItemProduct())->setProduct($product1);
        $product2 = (new ProductStub())->setId(22);
        $kitItemProduct2 = (new ProductKitItemProduct())->setProduct($product2);
        $kitItem = (new ProductKitItem())
            ->addKitItemProduct($kitItemProduct1)
            ->addKitItemProduct($kitItemProduct2);

        $this->kitItemProductAvailabilityChecker
            ->expects(self::exactly(2))
            ->method('isAvailable')
            ->withConsecutive([$kitItemProduct1], [$kitItemProduct2])
            ->willReturnOnConsecutiveCalls(true, false);

        self::assertEquals([$product1], $this->provider->getAvailableProducts($kitItem));
    }

    public function testGetProductsAvailableForPurchaseWhenHasKitItemProductsButNoAvailable(): void
    {
        $product1 = (new ProductStub())->setId(11);
        $kitItemProduct1 = (new ProductKitItemProduct())->setProduct($product1);
        $product2 = (new ProductStub())->setId(22);
        $kitItemProduct2 = (new ProductKitItemProduct())->setProduct($product2);
        $kitItem = (new ProductKitItem())
            ->addKitItemProduct($kitItemProduct1)
            ->addKitItemProduct($kitItemProduct2);

        $this->kitItemProductAvailabilityChecker
            ->expects(self::exactly(2))
            ->method('isAvailable')
            ->withConsecutive([$kitItemProduct1], [$kitItemProduct2])
            ->willReturnOnConsecutiveCalls(false, false);

        self::assertEquals([], $this->provider->getAvailableProducts($kitItem));
    }

    public function testGetFirstProductAvailableForPurchaseWhenNoKitItemProducts(): void
    {
        self::assertNull($this->provider->getFirstAvailableProduct(new ProductKitItem()));
    }

    public function testGetFirstProductAvailableForPurchaseWhenHasKitItemProducts(): void
    {
        $product1 = (new ProductStub())->setId(11);
        $kitItemProduct1 = (new ProductKitItemProduct())->setProduct($product1);
        $product2 = (new ProductStub())->setId(22);
        $kitItemProduct2 = (new ProductKitItemProduct())->setProduct($product2);
        $kitItem = (new ProductKitItem())
            ->addKitItemProduct($kitItemProduct1)
            ->addKitItemProduct($kitItemProduct2);

        $this->kitItemProductAvailabilityChecker
            ->expects(self::exactly(2))
            ->method('isAvailable')
            ->withConsecutive([$kitItemProduct1], [$kitItemProduct2])
            ->willReturnOnConsecutiveCalls(false, true);

        self::assertEquals($product2, $this->provider->getFirstAvailableProduct($kitItem));
    }

    public function testGetFirstProductAvailableForPurchaseWhenHasKitItemProductsButNoAvailable(): void
    {
        $product1 = (new ProductStub())->setId(11);
        $kitItemProduct1 = (new ProductKitItemProduct())->setProduct($product1);
        $product2 = (new ProductStub())->setId(22);
        $kitItemProduct2 = (new ProductKitItemProduct())->setProduct($product2);
        $kitItem = (new ProductKitItem())
            ->addKitItemProduct($kitItemProduct1)
            ->addKitItemProduct($kitItemProduct2);

        $this->kitItemProductAvailabilityChecker
            ->expects(self::exactly(2))
            ->method('isAvailable')
            ->withConsecutive([$kitItemProduct1], [$kitItemProduct2])
            ->willReturnOnConsecutiveCalls(false, false);

        self::assertNull($this->provider->getFirstAvailableProduct($kitItem));
    }
}
