<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Entity;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product as ProductStub;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class OrderLineItemTest extends \PHPUnit\Framework\TestCase
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

    public function testProperties()
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', '123'],
            ['order', new Order()],
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
            ['shippingEstimateAmount', 10.00]
        ];

        $entity = new OrderLineItem();
        $this->assertPropertyAccessors($entity, $properties);
    }

    public function testCreatePrice()
    {
        $entity = new OrderLineItem();
        $this->assertEmpty($entity->getPrice());
        $entity->setValue(42);
        $entity->setCurrency('USD');
        $entity->createPrice();
        $this->assertEquals(Price::create(42, 'USD'), $entity->getPrice());
    }

    public function testPriceNotInitializedWithValueWithoutCurrency()
    {
        $orderLineItem = new OrderLineItem();
        $this->assertEmpty($orderLineItem->getPrice());
        $orderLineItem->setValue(42);
        $this->assertEmpty($orderLineItem->getPrice());
    }

    public function testPriceNotInitializedWithCurrencyWithoutValue()
    {
        $orderLineItem = new OrderLineItem();
        $this->assertEmpty($orderLineItem->getPrice());
        $orderLineItem->setCurrency('USD');
        $this->assertEmpty($orderLineItem->getPrice());
    }

    public function testCreatePriceCalledOnSetCurrency()
    {
        $entity = new OrderLineItem();
        $this->assertEmpty($entity->getPrice());
        $entity->setValue(42);
        $this->assertEmpty($entity->getPrice());
        $entity->setCurrency('USD');
        $this->assertEquals(Price::create(42, 'USD'), $entity->getPrice());
    }

    public function testCreatePriceCalledOnSetValue()
    {
        $entity = new OrderLineItem();
        $this->assertEmpty($entity->getPrice());
        $entity->setCurrency('USD');
        $this->assertEmpty($entity->getPrice());
        $entity->setValue(42);
        $this->assertEquals(Price::create(42, 'USD'), $entity->getPrice());
    }

    public function testPrePersist()
    {
        $entity = new OrderLineItem();
        $entity->setPrice(Price::create(42, 'USD'));
        $this->assertEquals(42, $entity->getValue());
        $this->assertEquals('USD', $entity->getCurrency());

        $entity->getPrice()->setValue(84);
        $entity->getPrice()->setCurrency('EUR');

        $this->assertEmpty($entity->getProductSku());
        $this->assertEmpty($entity->getProductUnitCode());

        $productName = new ProductName();
        $productName->setString('Product Test Name');

        $product = new ProductStub();
        $product->setSku('SKU')
            ->addName($productName)
            ->prePersist();

        $entity->setProduct($product);
        $entity->setProductUnit((new ProductUnit())->setCode('kg'));

        $entity->preSave();
        $this->assertEquals(84, $entity->getValue());
        $this->assertEquals('EUR', $entity->getCurrency());
        $this->assertEquals('SKU', $entity->getProductSku());
        $this->assertEquals('Product Test Name', $entity->getProductName());
        $this->assertEquals('kg', $entity->getProductUnitCode());
    }

    /**
     * @dataProvider isRequirePriceRecalculationDataProvider
     */
    public function testIsRequirePriceRecalculation(
        OrderLineItem $entity,
        string $method,
        mixed $value,
        bool $expectedResult
    ) {
        ReflectionUtil::setPropertyValue($entity, 'requirePriceRecalculation', false);
        $this->assertFalse($entity->isRequirePriceRecalculation());

        $entity->$method($value);
        $this->assertEquals($expectedResult, $entity->isRequirePriceRecalculation());
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
                true
            ],
            [
                new OrderLineItem(),
                'setProductUnit',
                new ProductUnit(),
                true
            ],
            [
                new OrderLineItem(),
                'setQuantity',
                1,
                true
            ],
            [
                $lineItemWithProduct,
                'setProduct',
                $this->getProduct(21),
                true
            ],
            [
                $lineItemWithProductUnit,
                'setProductUnit',
                $this->getProductUnit('item'),
                true
            ],
            [
                $lineItemWithQuantity,
                'setQuantity',
                1,
                true
            ]
        ];
    }

    public function testShippingCost()
    {
        $lineItem = new OrderLineItem();
        $lineItem->setCurrency('USD');
        $lineItem->setShippingEstimateAmount(7.00);
        $shippingCost = $lineItem->getShippingCost();

        $this->assertInstanceOf(Price::class, $shippingCost);
        $this->assertEquals(7.00, $shippingCost->getValue());
        $this->assertEquals('USD', $shippingCost->getCurrency());
    }
}
