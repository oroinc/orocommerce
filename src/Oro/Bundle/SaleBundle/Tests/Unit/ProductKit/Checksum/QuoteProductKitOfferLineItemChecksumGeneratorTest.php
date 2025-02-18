<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\ProductKit\Checksum;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductKitItemLineItemsAwareStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductKitItemLineItem;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\ProductKit\Checksum\QuoteProductKitOfferLineItemChecksumGenerator;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;

final class QuoteProductKitOfferLineItemChecksumGeneratorTest extends TestCase
{
    private QuoteProductKitOfferLineItemChecksumGenerator $generator;

    #[\Override]
    protected function setUp(): void
    {
        $this->generator = new QuoteProductKitOfferLineItemChecksumGenerator();
    }

    public function testGetChecksumWhenNoQuoteProductOffer(): void
    {
        self::assertNull($this->generator->getChecksum(new QuoteProductOffer()));
    }

    public function testGetChecksumWhenNotQuoteProductOfferItem(): void
    {
        $lineItem = (new ProductKitItemLineItemsAwareStub(1))
            ->setProduct((new Product())->setType(Product::TYPE_KIT));

        self::assertNull($this->generator->getChecksum($lineItem));
    }

    public function testGetChecksumWhenNoQuoteKitItemLineItems(): void
    {
        $product = (new ProductStub())->setId(42)->setType(Product::TYPE_KIT);
        $quoteProduct = (new QuoteProduct())->setProduct($product);
        $lineItem = (new QuoteProductOffer())->setQuoteProduct($quoteProduct);

        self::assertEquals('0|42||0|', $this->generator->getChecksum($lineItem));
    }

    public function testGetChecksumWhenSkipQuoteKitItemLineItems(): void
    {
        $productUnitItem = (new ProductUnit())->setCode('item');

        $kitItem1 = new ProductKitItemStub(10);
        $kitItem1Product = (new ProductStub())->setId(4242);
        $productUnitSet = (new ProductUnit())->setCode('set');
        $kitItemLineItem1 = (new QuoteProductKitItemLineItem())
            ->setKitItem($kitItem1)
            ->setProduct($kitItem1Product)
            ->setQuantity(11)
            ->setProductUnit($productUnitSet);

        $kitItem2 = new ProductKitItemStub(20);
        $kitItem2Product = new ProductStub();
        $productUnitEach = (new ProductUnit())->setCode('each');
        $kitItemLineItem2 = (new QuoteProductKitItemLineItem())
            ->setKitItem($kitItem2)
            ->setProduct($kitItem2Product)
            ->setQuantity(22)
            ->setProductUnit($productUnitEach);

        $product = (new ProductStub())->setId(42)->setType(Product::TYPE_KIT);
        $quoteProduct = (new QuoteProduct())->setProduct($product);
        $quoteProduct->addKitItemLineItem($kitItemLineItem1);
        $quoteProduct->addKitItemLineItem($kitItemLineItem2);

        $lineItem = (new QuoteProductOffer())->setQuoteProduct($quoteProduct);
        ReflectionUtil::setId($lineItem, 99);
        $lineItem->setProductUnit($productUnitItem);
        $lineItem->setPrice(Price::create(99.99, 'USD'));

        self::assertEquals(
            '1|42|item|99.99|USD|10|4242|11|set',
            $this->generator->getChecksum($lineItem)
        );
    }

    public function testGetChecksumWhenHasQuoteKitItemLineItems(): void
    {
        $productUnitItem = (new ProductUnit())->setCode('item');

        $kitItem1 = new ProductKitItemStub(10);
        $kitItem1Product = (new ProductStub())->setId(4242);
        $productUnitSet = (new ProductUnit())->setCode('set');
        $kitItemLineItem1 = (new QuoteProductKitItemLineItem())
            ->setKitItem($kitItem1)
            ->setProduct($kitItem1Product)
            ->setQuantity(11)
            ->setProductUnit($productUnitSet);

        $kitItem2 = new ProductKitItemStub(20);
        $kitItem2Product = (new ProductStub())->setId(424242);
        $productUnitEach = (new ProductUnit())->setCode('each');
        $kitItemLineItem2 = (new QuoteProductKitItemLineItem())
            ->setKitItem($kitItem2)
            ->setProduct($kitItem2Product)
            ->setQuantity(22)
            ->setProductUnit($productUnitEach);

        $product = (new ProductStub())->setId(42)->setType(Product::TYPE_KIT);
        $quoteProduct = (new QuoteProduct())->setProduct($product);
        $quoteProduct->addKitItemLineItem($kitItemLineItem1);
        $quoteProduct->addKitItemLineItem($kitItemLineItem2);

        $lineItem = (new QuoteProductOffer())->setQuoteProduct($quoteProduct);
        $lineItem->setProductUnit($productUnitItem);
        $lineItem->setPrice(Price::create(99.99, 'USD'));

        self::assertEquals(
            '0|42|item|99.99|USD|20|424242|22|each|10|4242|11|set',
            $this->generator->getChecksum($lineItem)
        );
    }
}
