<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Entity;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Entity\RequestProductKitItemLineItem;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class RequestProductItemTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $checksum = sha1('sample-line-item');
        $properties = [
            ['id', 123],
            ['requestProduct', new RequestProduct()],
            ['quantity', 11],
            ['productUnit', new ProductUnit()],
            ['productUnitCode', 'unit-code'],
            ['price', new Price()],
            ['checksum', $checksum],
        ];

        $entity = new RequestProductItem();
        self::assertPropertyAccessors($entity, $properties);
    }

    public function testKitItemLineItems(): void
    {
        $requestProductItem = new RequestProductItem();

        self::assertCount(0, $requestProductItem->getKitItemLineItems());

        $requestProduct = new RequestProduct();
        $requestProductItem->setRequestProduct($requestProduct);

        self::assertCount(0, $requestProductItem->getKitItemLineItems());

        $productKitItem = new ProductKitItemStub(42);
        $kitItemLineItem = (new RequestProductKitItemLineItem())
            ->setKitItem($productKitItem);

        $requestProduct->addKitItemLineItem($kitItemLineItem);

        self::assertEquals(
            [$productKitItem->getId() => (clone $kitItemLineItem)->setLineItem($requestProductItem)],
            $requestProductItem->getKitItemLineItems()->toArray()
        );
    }

    public function testGetEntityIdentifier(): void
    {
        $item = new RequestProductItem();
        $value = 321;
        ReflectionUtil::setId($item, $value);
        self::assertSame($value, $item->getEntityIdentifier());
    }

    public function testGetProductHolder(): void
    {
        $requestProduct = new RequestProduct();

        $item = new RequestProductItem();
        $item->setRequestProduct($requestProduct);

        self::assertSame($requestProduct, $item->getProductHolder());
    }

    public function testSetProductUnit(): void
    {
        $productUnit = (new ProductUnit())->setCode('rfp-unit-code');
        $requestProductItem = new RequestProductItem();

        self::assertNull($requestProductItem->getProductUnitCode());

        $requestProductItem->setProductUnit($productUnit);

        self::assertEquals($productUnit->getCode(), $requestProductItem->getProductUnitCode());
    }

    public function testSetPrice(): void
    {
        $price = Price::create(22, 'EUR');

        $item = new RequestProductItem();
        $item->setPrice($price);

        self::assertEquals($price, $item->getPrice());

        self::assertEquals(22, ReflectionUtil::getPropertyValue($item, 'value'));
        self::assertEquals('EUR', ReflectionUtil::getPropertyValue($item, 'currency'));

        $item->setValue(34.5678);
        self::assertEquals(Price::create(34.5678, 'EUR'), $item->getPrice());

        $item->setCurrency('USD');
        self::assertEquals(Price::create(34.5678, 'USD'), $item->getPrice());
    }

    public function testSetPriceWhenInvalid(): void
    {
        $entity = new OrderProductKitItemLineItem();

        $price = Price::create('foobar', 'USD');
        $entity->setPrice($price);
        self::assertSame($price->getCurrency(), $entity->getCurrency());
        self::assertSame(0.0, $entity->getValue());
        self::assertSame($price, $entity->getPrice());
    }

    public function testLoadPrice(): void
    {
        $item = new RequestProductItem();

        self::assertNull($item->getPrice());

        ReflectionUtil::setPropertyValue($item, 'value', 10);
        ReflectionUtil::setPropertyValue($item, 'currency', 'USD');

        $item->loadPrice();

        self::assertEquals(Price::create(10, 'USD'), $item->getPrice());
    }

    public function testUpdatePrice(): void
    {
        $item = new RequestProductItem();
        $item->setPrice(Price::create(11, 'EUR'));

        $item->updatePrice();

        self::assertEquals(11, ReflectionUtil::getPropertyValue($item, 'value'));
        self::assertEquals('EUR', ReflectionUtil::getPropertyValue($item, 'currency'));
    }
}
