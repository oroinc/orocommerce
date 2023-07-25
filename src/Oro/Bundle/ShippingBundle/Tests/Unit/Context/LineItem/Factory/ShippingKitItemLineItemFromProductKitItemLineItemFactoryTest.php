<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context\LineItem\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
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
            1,
            (new ProductUnit())->setCode('item'),
            Price::create(1, 'USD'),
            (new ProductStub())->setId(1)
        );

        $expectedShippingKitItemLineItem = $this->getShippingKitItemLineItem(
            $productKitItemLineItem->getProductUnit(),
            $productKitItemLineItem->getQuantity(),
            $productKitItemLineItem->getPrice(),
            $productKitItemLineItem->getProduct(),
            $productKitItemLineItem,
            $productKitItemLineItem->getSortOrder(),
            $productKitItemLineItem->getKitItem()
        );

        self::assertEquals(
            $expectedShippingKitItemLineItem,
            $this->factory->create($productKitItemLineItem)
        );
    }

    public function testCreateCollection(): void
    {
        $productKitItemLineItem1 = $this->getKitItemLineItem(
            1,
            (new ProductUnit())->setCode('item'),
            Price::create(1, 'USD'),
            (new ProductStub())->setId(1)
        );
        $productKitItemLineItem2 = $this->getKitItemLineItem(
            1,
            (new ProductUnit())->setCode('set'),
            Price::create(2, 'USD'),
            (new ProductStub())->setId(2)
        );

        $productKitItemLineItems = new ArrayCollection([
            $productKitItemLineItem1,
            $productKitItemLineItem2,
        ]);

        $expectedShippingKitItemLineItems = [
            $this->getShippingKitItemLineItem(
                $productKitItemLineItem1->getProductUnit(),
                $productKitItemLineItem1->getQuantity(),
                $productKitItemLineItem1->getPrice(),
                $productKitItemLineItem1->getProduct(),
                $productKitItemLineItem1,
                $productKitItemLineItem1->getSortOrder(),
                $productKitItemLineItem1->getKitItem()
            ),
            $this->getShippingKitItemLineItem(
                $productKitItemLineItem2->getProductUnit(),
                $productKitItemLineItem2->getQuantity(),
                $productKitItemLineItem2->getPrice(),
                $productKitItemLineItem2->getProduct(),
                $productKitItemLineItem2,
                $productKitItemLineItem2->getSortOrder(),
                $productKitItemLineItem2->getKitItem()
            )
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
            ->setUnit($productUnit)
            ->setQuantity($quantity)
            ->setPrice($price)
            ->setSortOrder(1)
            ->setKitItem(new ProductKitItem());
    }

    private function getShippingKitItemLineItem(
        ?ProductUnit $productUnit,
        float $quantity,
        ?Price $price,
        ?Product $product,
        ?ProductHolderInterface $productHolder,
        int $sortOrder,
        ?ProductKitItem $kitItem
    ): ShippingKitItemLineItem {
        return (new ShippingKitItemLineItem(
            $productUnit,
            $productUnit->getCode(),
            $quantity,
            $productHolder
        ))
            ->setProduct($product)
            ->setProductSku($product->getSku())
            ->setPrice($price)
            ->setKitItem($kitItem)
            ->setSortOrder($sortOrder);
    }
}
