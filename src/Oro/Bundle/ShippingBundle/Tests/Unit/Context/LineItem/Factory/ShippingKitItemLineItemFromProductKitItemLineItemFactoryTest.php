<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context\LineItem\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ShippingBundle\Context\LineItem\Factory\ShippingKitItemLineItemFromProductKitItemLineItemFactory;
use Oro\Bundle\ShippingBundle\Context\ShippingKitItemLineItem;
use PHPUnit\Framework\TestCase;

class ShippingKitItemLineItemFromProductKitItemLineItemFactoryTest extends TestCase
{
    private ShippingKitItemLineItemFromProductKitItemLineItemFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new ShippingKitItemLineItemFromProductKitItemLineItemFactory();
    }

    public function testCreate(): void
    {
        $productKitItemLineItem = $this->getKitItemLineItem(
            12.3456,
            (new ProductUnit())->setCode('item'),
            Price::create(1, 'USD'),
            (new ProductStub())->setId(1)->setSku('sku1')
        );

        $expectedShippingKitItemLineItem = (new ShippingKitItemLineItem($productKitItemLineItem))
            ->setProduct($productKitItemLineItem->getProduct())
            ->setProductSku($productKitItemLineItem->getProductSku())
            ->setProductUnit($productKitItemLineItem->getProductUnit())
            ->setProductUnitCode($productKitItemLineItem->getProductUnitCode())
            ->setQuantity($productKitItemLineItem->getQuantity())
            ->setPrice($productKitItemLineItem->getPrice())
            ->setKitItem($productKitItemLineItem->getKitItem())
            ->setSortOrder($productKitItemLineItem->getSortOrder());

        self::assertEquals(
            $expectedShippingKitItemLineItem,
            $this->factory->create($productKitItemLineItem)
        );
    }

    public function testCreateWhenNoProductUnit(): void
    {
        $productKitItemLineItem = $this->getKitItemLineItem(
            1,
            (new ProductUnit())->setCode('item'),
            Price::create(1, 'USD'),
            (new ProductStub())->setId(1)
        );

        $productKitItemLineItem->setProductUnit(null);
        $productKitItemLineItem->setProductUnitCode('set');

        $expectedShippingKitItemLineItem = (new ShippingKitItemLineItem($productKitItemLineItem))
            ->setProduct($productKitItemLineItem->getProduct())
            ->setProductSku($productKitItemLineItem->getProductSku())
            ->setProductUnit(null)
            ->setProductUnitCode($productKitItemLineItem->getProductUnitCode())
            ->setQuantity($productKitItemLineItem->getQuantity())
            ->setPrice($productKitItemLineItem->getPrice())
            ->setKitItem($productKitItemLineItem->getKitItem())
            ->setSortOrder($productKitItemLineItem->getSortOrder());

        self::assertEquals(
            $expectedShippingKitItemLineItem,
            $this->factory->create($productKitItemLineItem)
        );
    }

    public function testCreateCollection(): void
    {
        $productKitItemLineItem1 = $this->getKitItemLineItem(
            12.3456,
            (new ProductUnit())->setCode('item'),
            Price::create(1, 'USD'),
            (new ProductStub())->setId(1)
        );
        $productKitItemLineItem2 = $this->getKitItemLineItem(
            23.4567,
            (new ProductUnit())->setCode('set'),
            Price::create(2, 'USD'),
            (new ProductStub())->setId(2)
        );

        $productKitItemLineItems = new ArrayCollection([
            $productKitItemLineItem1,
            $productKitItemLineItem2,
        ]);

        $expectedShippingKitItemLineItems = [
            (new ShippingKitItemLineItem($productKitItemLineItem1))
                ->setProduct($productKitItemLineItem1->getProduct())
                ->setProductSku($productKitItemLineItem1->getProductSku())
                ->setProductUnit($productKitItemLineItem1->getProductUnit())
                ->setProductUnitCode($productKitItemLineItem1->getProductUnitCode())
                ->setQuantity($productKitItemLineItem1->getQuantity())
                ->setPrice($productKitItemLineItem1->getPrice())
                ->setKitItem($productKitItemLineItem1->getKitItem())
                ->setSortOrder($productKitItemLineItem1->getSortOrder()),
            (new ShippingKitItemLineItem($productKitItemLineItem2))
                ->setProduct($productKitItemLineItem2->getProduct())
                ->setProductSku($productKitItemLineItem2->getProductSku())
                ->setProductUnit($productKitItemLineItem2->getProductUnit())
                ->setProductUnitCode($productKitItemLineItem2->getProductUnitCode())
                ->setQuantity($productKitItemLineItem2->getQuantity())
                ->setPrice($productKitItemLineItem2->getPrice())
                ->setKitItem($productKitItemLineItem2->getKitItem())
                ->setSortOrder($productKitItemLineItem2->getSortOrder()),
        ];

        self::assertEquals(
            new ArrayCollection($expectedShippingKitItemLineItems),
            $this->factory->createCollection($productKitItemLineItems)
        );
    }

    private function getKitItemLineItem(
        float $quantity,
        ?ProductUnit $productUnit,
        ?Price $price,
        ?Product $product,
    ): OrderProductKitItemLineItem {
        return (new OrderProductKitItemLineItem())
            ->setProduct($product)
            ->setProductUnit($productUnit)
            ->setQuantity($quantity)
            ->setPrice($price)
            ->setSortOrder(1)
            ->setKitItem(new ProductKitItemStub());
    }
}
