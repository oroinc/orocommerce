<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Entity;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product as ProductStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OrderLineItemTest extends TestCase
{
    use EntityTestCaseTrait;

    private function getProduct(int $id): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $id);

        return $product;
    }

    private function getProductUnit(string $code): ProductUnit
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode($code);

        return $productUnit;
    }

    public function testProperties(): void
    {
        $now = new \DateTime('now');
        $checksum = sha1('sample-line-item');
        $properties = [
            ['id', 123],
            ['product', new ProductStub()],
            ['parentProduct', new ProductStub()],
            ['productSku', '1234'],
            ['productName', 'name', ''],
            ['freeFormProduct', 'Services'],
            ['quantity', 42],
            ['productUnit', new ProductUnit()],
            ['productUnitCode', 'item'],
            ['value', 42.00],
            ['currency', 'USD'],
            ['price', Price::create(42, 'USD')],
            ['priceType', 10],
            ['shipBy', $now],
            ['fromExternalSource', true],
            ['comment', 'The answer is 42'],
            ['shippingMethod', 'shipping_method'],
            ['shippingMethodType', 'shipping_method_type'],
            ['shippingEstimateAmount', 10.00],
            ['checksum', $checksum],
        ];

        $entity = new OrderLineItem();
        self::assertPropertyAccessors($entity, $properties);

        $productKitItem = new ProductKitItemStub(42);
        $orderProductKitItemLineItem = (new OrderProductKitItemLineItem())
            ->setKitItem($productKitItem);

        self::assertSame([], $entity->getKitItemLineItems()->toArray());

        $entity->addKitItemLineItem($orderProductKitItemLineItem);
        self::assertSame(
            [$productKitItem->getId() => $orderProductKitItemLineItem],
            $entity->getKitItemLineItems()->toArray()
        );

        $entity->removeKitItemLineItem($orderProductKitItemLineItem);
        self::assertSame([], $entity->getKitItemLineItems()->toArray());
        self::assertPropertyCollection($entity, 'orders', new Order());
    }

    public function testCreatePrice(): void
    {
        $entity = new OrderLineItem();
        self::assertEmpty($entity->getPrice());
        $entity->setValue(42);
        $entity->setCurrency('USD');
        $entity->createPrice();
        self::assertEquals(Price::create(42, 'USD'), $entity->getPrice());
    }

    public function testPriceNotInitializedWithValueWithoutCurrency(): void
    {
        $orderLineItem = new OrderLineItem();
        self::assertEmpty($orderLineItem->getPrice());
        $orderLineItem->setValue(42);
        self::assertEmpty($orderLineItem->getPrice());
    }

    public function testPriceNotInitializedWithCurrencyWithoutValue(): void
    {
        $orderLineItem = new OrderLineItem();
        self::assertEmpty($orderLineItem->getPrice());
        $orderLineItem->setCurrency('USD');
        self::assertEmpty($orderLineItem->getPrice());
    }

    public function testCreatePriceCalledOnSetCurrency(): void
    {
        $entity = new OrderLineItem();
        self::assertEmpty($entity->getPrice());
        $entity->setValue(42);
        self::assertEmpty($entity->getPrice());
        $entity->setCurrency('USD');
        self::assertEquals(Price::create(42, 'USD'), $entity->getPrice());
    }

    public function testCreatePriceCalledOnSetValue(): void
    {
        $entity = new OrderLineItem();
        self::assertEmpty($entity->getPrice());
        $entity->setCurrency('USD');
        self::assertEmpty($entity->getPrice());
        $entity->setValue(42);
        self::assertEquals(Price::create(42, 'USD'), $entity->getPrice());
    }

    public function testPrePersist(): void
    {
        $entity = new OrderLineItem();
        $entity->setPrice(Price::create(42, 'USD'));
        self::assertEquals(42, $entity->getValue());
        self::assertEquals('USD', $entity->getCurrency());

        $entity->getPrice()->setValue(84);
        $entity->getPrice()->setCurrency('EUR');

        self::assertEmpty($entity->getProductSku());
        self::assertEmpty($entity->getProductUnitCode());

        $productName = new ProductName();
        $productName->setString('Product Test Name');

        $product = new ProductStub();
        $product->setSku('SKU')
            ->addName($productName)
            ->prePersist();

        $entity->setProduct($product);
        $entity->setProductUnit((new ProductUnit())->setCode('kg'));

        $entity->preSave();
        self::assertEquals(84, $entity->getValue());
        self::assertEquals('EUR', $entity->getCurrency());
        self::assertEquals('SKU', $entity->getProductSku());
        self::assertEquals('Product Test Name', $entity->getProductName());
        self::assertEquals('kg', $entity->getProductUnitCode());
    }

    /**
     * @dataProvider isRequirePriceRecalculationDataProvider
     */
    public function testIsRequirePriceRecalculation(
        OrderLineItem $entity,
        string $method,
        mixed $value,
        bool $expectedResult
    ): void {
        ReflectionUtil::setPropertyValue($entity, 'requirePriceRecalculation', false);
        self::assertFalse($entity->isRequirePriceRecalculation());

        $entity->$method($value);
        self::assertEquals($expectedResult, $entity->isRequirePriceRecalculation());
    }

    public function isRequirePriceRecalculationDataProvider(): array
    {
        $lineItemWithProduct = new OrderLineItem();
        $lineItemWithProduct->setProduct($this->getProduct(42));

        $lineItemWithProductUnit = new OrderLineItem();
        $lineItemWithProductUnit->setProductUnit($this->getProductUnit('kg'));

        $lineItemWithQuantity = new OrderLineItem();
        $lineItemWithQuantity->setQuantity(21);

        return [
            [
                new OrderLineItem(),
                'setProduct',
                new ProductStub(),
                true,
            ],
            [
                new OrderLineItem(),
                'setProductUnit',
                new ProductUnit(),
                true,
            ],
            [
                new OrderLineItem(),
                'setQuantity',
                1,
                true,
            ],
            [
                $lineItemWithProduct,
                'setProduct',
                $this->getProduct(21),
                true,
            ],
            [
                $lineItemWithProductUnit,
                'setProductUnit',
                $this->getProductUnit('item'),
                true,
            ],
            [
                $lineItemWithQuantity,
                'setQuantity',
                1,
                true,
            ],
        ];
    }

    public function testPrice(): void
    {
        $lineItem = new OrderLineItem();
        self::assertNull($lineItem->getPrice());
        self::assertNull($lineItem->getCurrency());
        self::assertNull($lineItem->getValue());

        $price = Price::create(12.3456, 'USD');
        $lineItem->setPrice($price);
        self::assertSame($price->getCurrency(), $lineItem->getCurrency());
        self::assertSame((float)$price->getValue(), $lineItem->getValue());

        $lineItem->setValue(34.5678);
        self::assertEquals(Price::create(34.5678, 'USD'), $lineItem->getPrice());

        $lineItem->setCurrency('EUR');
        self::assertEquals(Price::create(34.5678, 'EUR'), $lineItem->getPrice());
    }

    public function testPriceWhenInvalid(): void
    {
        $lineItem = new OrderLineItem();

        $price = Price::create('foobar', 'USD');
        $lineItem->setPrice($price);
        self::assertSame($price->getCurrency(), $lineItem->getCurrency());
        self::assertSame(0.0, $lineItem->getValue());
        self::assertSame($price, $lineItem->getPrice());
    }

    public function testShippingCost(): void
    {
        $lineItem = new OrderLineItem();
        $lineItem->setCurrency('USD');
        $lineItem->setShippingEstimateAmount(7.00);
        $shippingCost = $lineItem->getShippingCost();

        self::assertInstanceOf(Price::class, $shippingCost);
        self::assertEquals(7.00, $shippingCost->getValue());
        self::assertEquals('USD', $shippingCost->getCurrency());
    }
}
