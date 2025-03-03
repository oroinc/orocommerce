<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\ProductKit\Factory;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\ProductKit\Provider\ProductKitItemsProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\ProductKit\Factory\ProductKitItemLineItemFactory;
use Oro\Bundle\ShoppingListBundle\ProductKit\Factory\ProductKitLineItemFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductKitLineItemFactoryTest extends TestCase
{
    private ProductKitItemsProvider&MockObject $productKitItemsProvider;
    private ProductKitItemLineItemFactory&MockObject $kitItemLineItemFactory;
    private ProductKitLineItemFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->productKitItemsProvider = $this->createMock(ProductKitItemsProvider::class);
        $this->kitItemLineItemFactory = $this->createMock(ProductKitItemLineItemFactory::class);

        $this->factory = new ProductKitLineItemFactory($this->productKitItemsProvider, $this->kitItemLineItemFactory);
    }

    public function testCreateProductKitLineItemWhenNoUnitNoQuantityNoShoppingListNoKitItems(): void
    {
        $product = new ProductStub();

        $this->productKitItemsProvider->expects(self::once())
            ->method('getAvailableKitItems')
            ->with($product)
            ->willReturn([]);

        $expected = (new LineItem())
            ->setProduct($product);

        self::assertEquals($expected, $this->factory->createProductKitLineItem($product));
    }

    public function testCreateProductKitLineItemWhenNoQuantityNoShoppingListNoKitItemsAndUnitFallbacksToProduct(): void
    {
        $product = new ProductStub();
        $productUnitItem = (new ProductUnit())->setCode('item');
        $unitPrecision = (new ProductUnitPrecision())->setUnit($productUnitItem)->setPrecision(2);
        $product->setPrimaryUnitPrecision($unitPrecision);

        $this->productKitItemsProvider->expects(self::once())
            ->method('getAvailableKitItems')
            ->with($product)
            ->willReturn([]);

        $expected = (new LineItem())
            ->setProduct($product)
            ->setUnit($productUnitItem)
            ->setQuantity(1);

        self::assertEquals($expected, $this->factory->createProductKitLineItem($product));
    }

    public function testCreateProductKitLineItemWhenNoQuantityNoShoppingListNoKitItems(): void
    {
        $product = new ProductStub();
        $productUnitItem = (new ProductUnit())->setCode('item');

        $this->productKitItemsProvider->expects(self::once())
            ->method('getAvailableKitItems')
            ->with($product)
            ->willReturn([]);

        $expected = (new LineItem())
            ->setProduct($product)
            ->setUnit($productUnitItem);

        self::assertEquals($expected, $this->factory->createProductKitLineItem($product, $productUnitItem));
    }

    public function testCreateProductKitLineItemWhenNoShoppingListNoKitItems(): void
    {
        $product = new ProductStub();
        $productUnitItem = (new ProductUnit())->setCode('item');
        $quantity = 11;

        $this->productKitItemsProvider->expects(self::once())
            ->method('getAvailableKitItems')
            ->with($product)
            ->willReturn([]);

        $expected = (new LineItem())
            ->setProduct($product)
            ->setUnit($productUnitItem)
            ->setQuantity($quantity);

        self::assertEquals($expected, $this->factory->createProductKitLineItem($product, $productUnitItem, $quantity));
    }

    public function testCreateProductKitLineItemWhenNoKitItems(): void
    {
        $product = new ProductStub();
        $productUnitItem = (new ProductUnit())->setCode('item');
        $quantity = 11;
        $customerUser = new CustomerUser();
        $organization = new Organization();
        $shoppingList = (new ShoppingList())
            ->setCustomerUser($customerUser)
            ->setOrganization($organization);

        $this->productKitItemsProvider->expects(self::once())
            ->method('getAvailableKitItems')
            ->with($product)
            ->willReturn([]);

        $expected = (new LineItem())
            ->setProduct($product)
            ->setUnit($productUnitItem)
            ->setQuantity($quantity)
            ->setShoppingList($shoppingList)
            ->setCustomerUser($shoppingList->getCustomerUser())
            ->setOrganization($shoppingList->getOrganization());

        self::assertEquals(
            $expected,
            $this->factory->createProductKitLineItem($product, $productUnitItem, $quantity, $shoppingList)
        );
    }

    public function testCreateProductKitLineItemWhenHasKitItems(): void
    {
        $product = new ProductStub();
        $productUnitItem = (new ProductUnit())->setCode('item');
        $quantity = 11;
        $customerUser = new CustomerUser();
        $organization = new Organization();
        $shoppingList = (new ShoppingList())
            ->setCustomerUser($customerUser)
            ->setOrganization($organization);
        $kitItem1 = new ProductKitItemStub(1);
        $kitItem2 = new ProductKitItemStub(2);

        $this->productKitItemsProvider->expects(self::once())
            ->method('getAvailableKitItems')
            ->with($product)
            ->willReturn([$kitItem1, $kitItem2]);

        $kitItemLineItem1 = (new ProductKitItemLineItem())
            ->setKitItem($kitItem1);
        $kitItemLineItem2 = (new ProductKitItemLineItem())
            ->setKitItem($kitItem2);

        $this->kitItemLineItemFactory->expects(self::exactly(2))
            ->method('createKitItemLineItem')
            ->withConsecutive([$kitItem1], [$kitItem2])
            ->willReturnOnConsecutiveCalls($kitItemLineItem1, $kitItemLineItem2);

        $expected = (new LineItem())
            ->setProduct($product)
            ->setUnit($productUnitItem)
            ->setQuantity($quantity)
            ->setShoppingList($shoppingList)
            ->setCustomerUser($shoppingList->getCustomerUser())
            ->setOrganization($shoppingList->getOrganization())
            ->addKitItemLineItem($kitItemLineItem1)
            ->addKitItemLineItem($kitItemLineItem2);

        self::assertEquals(
            $expected,
            $this->factory->createProductKitLineItem($product, $productUnitItem, $quantity, $shoppingList)
        );
    }

    public function testAddKitItemLineItemsAvailableForPurchase(): void
    {
        $product = new ProductStub();
        $kitItem1 = new ProductKitItemStub(1);
        $kitItem2 = new ProductKitItemStub(2);

        $this->productKitItemsProvider->expects(self::once())
            ->method('getAvailableKitItems')
            ->with($product)
            ->willReturn([$kitItem1, $kitItem2]);

        $kitItemLineItem1 = (new ProductKitItemLineItem())
            ->setKitItem($kitItem1);
        $kitItemLineItem2 = (new ProductKitItemLineItem())
            ->setKitItem($kitItem2);

        $this->kitItemLineItemFactory->expects(self::once())
            ->method('createKitItemLineItem')
            ->with($kitItem2)
            ->willReturn($kitItemLineItem2);

        $lineItem = (new LineItem())
            ->setProduct($product)
            ->addKitItemLineItem($kitItemLineItem1);

        $this->factory->addKitItemLineItemsAvailableForPurchase($lineItem);

        $kitItemLineItems = $lineItem->getKitItemLineItems();
        self::assertCount(2, $kitItemLineItems);
        self::assertTrue($kitItemLineItems->contains($kitItemLineItem1));
        self::assertTrue($kitItemLineItems->contains($kitItemLineItem2));
    }
}
