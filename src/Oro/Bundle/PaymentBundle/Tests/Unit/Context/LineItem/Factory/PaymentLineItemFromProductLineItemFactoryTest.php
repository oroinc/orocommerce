<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Context\LineItem\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\PaymentBundle\Context\LineItem\Factory\PaymentKitItemLineItemFromProductKitItemLineItemFactory;
use Oro\Bundle\PaymentBundle\Context\LineItem\Factory\PaymentLineItemFromProductLineItemFactory;
use Oro\Bundle\PaymentBundle\Context\PaymentKitItemLineItem;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\TestCase;

class PaymentLineItemFromProductLineItemFactoryTest extends TestCase
{
    private const TEST_QUANTITY = 15;

    private PaymentLineItemFromProductLineItemFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->factory = new PaymentLineItemFromProductLineItemFactory(
            new PaymentKitItemLineItemFromProductKitItemLineItemFactory()
        );
    }

    public function testCreateByProductLineItemInterfaceOnly(): void
    {
        $product = $this->getProduct(1001);
        $unit = $this->getProductUnit('item');
        $productLineItem = $this->createProductLineItemInterfaceMock(
            $unit,
            $unit->getCode(),
            self::TEST_QUANTITY,
            $product,
            $product->getSku()
        );

        $expectedPaymentLineItem = $this->createPaymentLineItem(
            $productLineItem->getQuantity(),
            $productLineItem->getProductUnit(),
            $productLineItem->getProductUnitCode(),
            $productLineItem,
            null,
            $productLineItem->getProduct(),
            null
        );

        self::assertEquals(
            $expectedPaymentLineItem,
            $this->factory->create($productLineItem)
        );
    }

    public function testCreate(): void
    {
        $product = $this->getProduct(1001);
        $unit = $this->getProductUnit('item');
        $kitItemLineItem = $this->createKitItemLineItem(
            self::TEST_QUANTITY,
            $unit,
            Price::create(13, 'USD'),
            $this->getProduct(1)
        );
        $orderLineItem = $this->createOrderLineItem(
            self::TEST_QUANTITY,
            $unit,
            Price::create(99.9, 'USD'),
            $product,
            [$kitItemLineItem]
        );

        $expectedPaymentKitItemLineItem = $this->createPaymentKitItemLineItem(
            $kitItemLineItem->getProductUnit(),
            $kitItemLineItem->getQuantity(),
            $kitItemLineItem->getPrice(),
            $kitItemLineItem->getProduct(),
            $kitItemLineItem,
            $kitItemLineItem->getSortOrder(),
            $kitItemLineItem->getKitItem()
        );
        $expectedPaymentLineItem = $this->createPaymentLineItem(
            $orderLineItem->getQuantity(),
            $orderLineItem->getProductUnit(),
            $orderLineItem->getProductUnitCode(),
            $orderLineItem,
            $orderLineItem->getPrice(),
            $orderLineItem->getProduct(),
            new ArrayCollection([$expectedPaymentKitItemLineItem])
        );

        self::assertEquals(
            $expectedPaymentLineItem,
            $this->factory->create($orderLineItem)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCreateCollection(): void
    {
        $product1 = $this->getProduct(1001);
        $product2 = $this->getProduct(2002);

        $unit = $this->getProductUnit('item');
        $kitItemLineItem1 = $this->createKitItemLineItem(
            self::TEST_QUANTITY,
            $unit,
            Price::create(1, 'USD'),
            $this->getProduct(1)
        );
        $orderLineItem1 = $this->createOrderLineItem(
            self::TEST_QUANTITY,
            $unit,
            Price::create(99.9, 'USD'),
            $product1,
            [$kitItemLineItem1]
        );

        $kitItemLineItem2 = $this->createKitItemLineItem(
            self::TEST_QUANTITY,
            $unit,
            Price::create(2, 'USD'),
            $this->getProduct(2)
        );
        $orderLineItem2 = $this->createOrderLineItem(
            self::TEST_QUANTITY,
            $unit,
            Price::create(99.9, 'USD'),
            $product2,
            [$kitItemLineItem2]
        );

        $productLineItems = [$orderLineItem1, $orderLineItem2];

        $expectedPaymentKitItemLineItem1 = $this->createPaymentKitItemLineItem(
            $kitItemLineItem1->getProductUnit(),
            $kitItemLineItem1->getQuantity(),
            $kitItemLineItem1->getPrice(),
            $kitItemLineItem1->getProduct(),
            $kitItemLineItem1,
            $kitItemLineItem1->getSortOrder(),
            $kitItemLineItem1->getKitItem()
        );
        $expectedPaymentLineItem1 = $this->createPaymentLineItem(
            $orderLineItem1->getQuantity(),
            $orderLineItem1->getProductUnit(),
            $orderLineItem1->getProductUnitCode(),
            $orderLineItem1,
            $orderLineItem1->getPrice(),
            $orderLineItem1->getProduct(),
            new ArrayCollection([$expectedPaymentKitItemLineItem1])
        );

        $expectedPaymentKitItemLineItem2 = $this->createPaymentKitItemLineItem(
            $kitItemLineItem2->getProductUnit(),
            $kitItemLineItem2->getQuantity(),
            $kitItemLineItem2->getPrice(),
            $kitItemLineItem2->getProduct(),
            $kitItemLineItem2,
            $kitItemLineItem2->getSortOrder(),
            $kitItemLineItem2->getKitItem()
        );
        $expectedPaymentLineItem2 = $this->createPaymentLineItem(
            $orderLineItem2->getQuantity(),
            $orderLineItem2->getProductUnit(),
            $orderLineItem2->getProductUnitCode(),
            $orderLineItem2,
            $orderLineItem2->getPrice(),
            $orderLineItem2->getProduct(),
            new ArrayCollection([$expectedPaymentKitItemLineItem2])
        );

        self::assertEquals(
            new ArrayCollection([$expectedPaymentLineItem1, $expectedPaymentLineItem2]),
            $this->factory->createCollection($productLineItems)
        );
    }

    public function testCreateCollectionEmpty(): void
    {
        self::assertEquals(new ArrayCollection([]), $this->factory->createCollection([]));
    }

    private function createOrderLineItem(
        float|int $quantity,
        ?ProductUnit $productUnit,
        ?Price $price,
        ?Product $product,
        array $kitItemLineItems = []
    ): OrderLineItem {
        $lineItem = new OrderLineItem();
        $lineItem->setQuantity($quantity);
        $lineItem->setProductUnit($productUnit);
        $lineItem->setPrice($price);
        $lineItem->setProduct($product);
        $lineItem->setProductUnitCode($productUnit->getCode());
        foreach ($kitItemLineItems as $kitItemLineItem) {
            $lineItem->addKitItemLineItem($kitItemLineItem);
        }

        return $lineItem;
    }

    private function createProductLineItemInterfaceMock(
        ProductUnit $productUnit,
        string $unitCode,
        float|int $quantity,
        ?Product $product,
        ?string $productSku
    ): ProductLineItemInterface {
        $lineItem = $this->createMock(ProductLineItemInterface::class);
        $lineItem->expects(self::any())
            ->method('getProductUnit')
            ->willReturn($productUnit);
        $lineItem->expects(self::any())
            ->method('getProductUnitCode')
            ->willReturn($unitCode);
        $lineItem->expects(self::any())
            ->method('getQuantity')
            ->willReturn($quantity);
        $lineItem->expects(self::any())
            ->method('getProduct')
            ->willReturn($product);
        $lineItem->expects(self::any())
            ->method('getProductSku')
            ->willReturn($productSku);

        return $lineItem;
    }

    private function getProduct(int $id): Product
    {
        return (new ProductStub())
            ->setId($id);
    }

    private function getProductUnit(string $code): ProductUnit
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode($code);

        return $productUnit;
    }

    private function createKitItemLineItem(
        float|int $quantity,
        ?ProductUnit $productUnit,
        ?Price $price,
        ?Product $product
    ): OrderProductKitItemLineItem {
        return (new OrderProductKitItemLineItem())
            ->setProduct($product)
            ->setProductUnit($productUnit)
            ->setQuantity($quantity)
            ->setPrice($price)
            ->setSortOrder(1);
    }

    private function createPaymentLineItem(
        float|int $quantity,
        ?ProductUnit $productUnit,
        string $unitCode,
        ProductLineItemInterface $productHolder,
        ?Price $price,
        ?Product $product,
        ?Collection $kitItemLineItems
    ): PaymentLineItem {
        $paymentLineItem = (new PaymentLineItem(
            $productUnit,
            $quantity,
            $productHolder
        ))
            ->setProductUnitCode($unitCode)
            ->setProduct($product)
            ->setProductSku($product->getSku());
        if ($price) {
            $paymentLineItem->setPrice($price);
        }
        if ($kitItemLineItems) {
            $paymentLineItem->setKitItemLineItems($kitItemLineItems);
        }

        return $paymentLineItem;
    }

    private function createPaymentKitItemLineItem(
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
