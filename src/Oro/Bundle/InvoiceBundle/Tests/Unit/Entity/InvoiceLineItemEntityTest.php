<?php

namespace Oro\Bundle\InvoiceBundle\Tests\Unit\Entity;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\InvoiceBundle\Entity\Invoice;
use Oro\Bundle\InvoiceBundle\Entity\InvoiceLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

class InvoiceLineItemEntityTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', '123'],
            ['product', new Product()],
            ['productSku', 'product-1234'],
            ['freeFormProduct', 'free form product'],
            ['quantity', 10],
            ['productUnit', new ProductUnit()],
            ['productUnitCode', 'product-unit-code'],
            ['priceType', InvoiceLineItem::PRICE_TYPE_UNIT],
            ['price', Price::create(9.99, 'USD'), false],
            ['invoice', new Invoice()],
        ];

        $invoiceLineItem = new InvoiceLineItem();
        $this->assertPropertyAccessors($invoiceLineItem, $properties);
    }

    public function testGetEntityIdentifier()
    {
        $invoiceLineItem = new InvoiceLineItem();
        $this->assertSame($invoiceLineItem->getId(), $invoiceLineItem->getEntityIdentifier());
    }

    public function testGetProductHolder()
    {
        $invoiceLineItem = new InvoiceLineItem();
        $this->assertSame($invoiceLineItem, $invoiceLineItem->getProductHolder());
    }

    public function testUpdateItemInformation()
    {
        $invoiceLineItem = new InvoiceLineItem();

        $product = new Product();
        $product->setSku('PROD1');

        $productUnit = new ProductUnit();
        $productUnit->setCode('PU1');

        $invoiceLineItem->setProduct($product)
            ->setProductUnit($productUnit);
        $invoiceLineItem->updateItemInformation();

        $this->assertSame('PROD1', $invoiceLineItem->getProductSku());
        $this->assertSame('PU1', $invoiceLineItem->getProductUnitCode());
    }

    public function testGetTotalPrice()
    {
        $invoiceLineItem = new InvoiceLineItem();
        $invoiceLineItem->setQuantity(5)
            ->setPrice(Price::create(1.1111, 'USD'));

        $this->assertEquals(5.5555, $invoiceLineItem->getTotalPrice()->getValue());
        $this->assertEquals('USD', $invoiceLineItem->getTotalPrice()->getCurrency());
    }
}
