<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Context\LineItem\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\PaymentBundle\Context\LineItem\Factory\PaymentKitItemLineItemFromProductKitItemLineItemFactory;
use Oro\Bundle\PaymentBundle\Context\PaymentKitItemLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\TestCase;

class PaymentKitItemLineItemFromProductKitItemLineItemFactoryTest extends TestCase
{
    private PaymentKitItemLineItemFromProductKitItemLineItemFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->factory = new PaymentKitItemLineItemFromProductKitItemLineItemFactory();
    }

    public function testCreate(): void
    {
        $productKitItemLineItem = $this->getKitItemLineItem(
            1,
            (new ProductUnit())->setCode('item'),
            Price::create(1, 'USD'),
            (new ProductStub())->setId(1)
        );

        $expectedPaymentKitItemLineItem = $this->getPaymentKitItemLineItem(
            $productKitItemLineItem->getProductUnit(),
            $productKitItemLineItem->getQuantity(),
            $productKitItemLineItem->getPrice(),
            $productKitItemLineItem->getProduct(),
            $productKitItemLineItem,
            $productKitItemLineItem->getSortOrder(),
            $productKitItemLineItem->getKitItem()
        );

        self::assertEquals(
            $expectedPaymentKitItemLineItem,
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

        $expectedPaymentKitItemLineItems = [
            $this->getPaymentKitItemLineItem(
                $productKitItemLineItem1->getProductUnit(),
                $productKitItemLineItem1->getQuantity(),
                $productKitItemLineItem1->getPrice(),
                $productKitItemLineItem1->getProduct(),
                $productKitItemLineItem1,
                $productKitItemLineItem1->getSortOrder(),
                $productKitItemLineItem1->getKitItem()
            ),
            $this->getPaymentKitItemLineItem(
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
            new ArrayCollection($expectedPaymentKitItemLineItems),
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

    private function getPaymentKitItemLineItem(
        ?ProductUnit $productUnit,
        float $quantity,
        ?Price $price,
        ?Product $product,
        ?ProductHolderInterface $productHolder,
        int $sortOrder,
        ?ProductKitItem $kitItem
    ): PaymentKitItemLineItem {
        return (new PaymentKitItemLineItem(
            $productUnit,
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
