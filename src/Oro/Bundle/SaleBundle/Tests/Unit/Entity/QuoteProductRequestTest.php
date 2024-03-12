<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Entity;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductKitItemLineItem;
use Oro\Bundle\SaleBundle\Entity\QuoteProductRequest;
use Oro\Bundle\SaleBundle\Model\BaseQuoteProductItem;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class QuoteProductRequestTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $checksum = sha1('sample-line-item');
        $properties = [
            ['id', 123],
            ['quoteProduct', new QuoteProduct()],
            ['quantity', 11],
            ['productUnit', new ProductUnit()],
            ['productUnitCode', 'unit-code'],
            ['price', new Price()],
            ['requestProductItem', new RequestProductItem()],
            ['checksum', $checksum],
        ];

        $entity = new QuoteProductRequest();
        self::assertPropertyAccessors($entity, $properties);
    }

    public function testKitItemLineItems(): void
    {
        $quoteProductItem = new BaseQuoteProductItem();

        self::assertCount(0, $quoteProductItem->getKitItemLineItems());

        $requestProduct = new QuoteProduct();
        $quoteProductItem->setQuoteProduct($requestProduct);

        self::assertCount(0, $quoteProductItem->getKitItemLineItems());

        $productKitItem = new ProductKitItemStub(42);
        $kitItemLineItem = (new QuoteProductKitItemLineItem())
            ->setKitItem($productKitItem);

        $requestProduct->addKitItemLineItem($kitItemLineItem);

        self::assertEquals(
            [$productKitItem->getId() => (clone $kitItemLineItem)->setLineItem($quoteProductItem)],
            $quoteProductItem->getKitItemLineItems()->toArray()
        );
    }

    public function testKitItemLineItemsCollectionIsInitializedWhenQuoteProductIsSet(): void
    {
        $quoteProductItem = new BaseQuoteProductItem();

        self::assertCount(0, $quoteProductItem->getKitItemLineItems());

        $requestProduct = new QuoteProduct();
        $productKitItem = new ProductKitItemStub(42);
        $kitItemLineItem = (new QuoteProductKitItemLineItem())
            ->setKitItem($productKitItem);
        $requestProduct->addKitItemLineItem($kitItemLineItem);

        $quoteProductItem->setQuoteProduct($requestProduct);

        self::assertEquals(
            [$productKitItem->getId() => (clone $kitItemLineItem)->setLineItem($quoteProductItem)],
            $quoteProductItem->getKitItemLineItems()->toArray()
        );
    }

    public function testPostLoad(): void
    {
        $item = new QuoteProductRequest();

        self::assertNull($item->getPrice());

        ReflectionUtil::setPropertyValue($item, 'value', 10);
        ReflectionUtil::setPropertyValue($item, 'currency', 'USD');

        $item->postLoad();

        self::assertEquals(Price::create(10, 'USD'), $item->getPrice());
    }

    public function testUpdatePrice(): void
    {
        $item = new QuoteProductRequest();
        $item->setPrice(Price::create(11, 'EUR'));

        $item->updatePrice();

        self::assertEquals(11, ReflectionUtil::getPropertyValue($item, 'value'));
        self::assertEquals('EUR', ReflectionUtil::getPropertyValue($item, 'currency'));
    }

    public function testSetPrice(): void
    {
        $price = Price::create(22, 'EUR');

        $item = new QuoteProductRequest();
        $item->setPrice($price);

        self::assertEquals($price, $item->getPrice());

        self::assertEquals(22, ReflectionUtil::getPropertyValue($item, 'value'));
        self::assertEquals('EUR', ReflectionUtil::getPropertyValue($item, 'currency'));
    }

    public function testSetProductUnit(): void
    {
        $item = new QuoteProductRequest();

        self::assertNull($item->getProductUnitCode());

        $item->setProductUnit((new ProductUnit())->setCode('kg'));

        self::assertEquals('kg', $item->getProductUnitCode());
    }
}
