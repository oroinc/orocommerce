<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Entity;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductRequest;

class QuoteProductTest extends AbstractTest
{
    public function testProperties()
    {
        $properties = [
            ['id', 123],
            ['quote', new Quote()],
            ['product', new Product()],
            ['freeFormProduct', 'free form product'],
            ['productSku', 'sku'],
            ['productReplacement', new Product()],
            ['freeFormProductReplacement', 'free form product replacement'],
            ['productReplacementSku', 'sku-replacement'],
            ['type', QuoteProduct::TYPE_OFFER],
            ['comment', 'Seller notes'],
            ['commentAccount', 'Account notes'],
        ];

        static::assertPropertyAccessors(new QuoteProduct(), $properties);

        static::assertPropertyCollections(new QuoteProduct(), [
            ['quoteProductOffers', new QuoteProductOffer()],
            ['quoteProductRequests', new QuoteProductRequest()],
        ]);
    }

    public function testGetEntityIdentifier()
    {
        $product = new QuoteProduct();
        $value = 321;
        $this->setProperty($product, 'id', $value);
        $this->assertEquals($value, $product->getEntityIdentifier());
    }

    public function testUpdateProducts()
    {
        $product = (new Product())->setSku('product-sku');
        $replacement = (new Product())->setSku('replacement-sku');

        $quoteProduct = new QuoteProduct();

        $this->assertNull($quoteProduct->getProductSku());
        $this->assertNull($quoteProduct->getFreeFormProduct());
        $this->assertNull($quoteProduct->getProductReplacementSku());
        $this->assertNull($quoteProduct->getFreeFormProductReplacement());

        $this->setProperty($quoteProduct, 'product', $product);
        $this->setProperty($quoteProduct, 'productReplacement', $replacement);

        $quoteProduct->updateProducts();

        $this->assertSame('product-sku', $quoteProduct->getProductSku());
        $this->assertSame((string)$product, $quoteProduct->getFreeFormProduct());
        $this->assertSame('replacement-sku', $quoteProduct->getProductReplacementSku());
        $this->assertSame((string)$replacement, $quoteProduct->getFreeFormProductReplacement());
    }

    public function testSetProduct()
    {
        $product = new QuoteProduct();

        $this->assertNull($product->getProductSku());
        $this->assertNull($product->getFreeFormProduct());

        $product->setProduct((new Product)->setSku('test-sku'));

        $this->assertEquals('test-sku', $product->getProductSku());
        $this->assertEquals((string)(new Product)->setSku('test-sku'), $product->getFreeFormProduct());
    }

    public function testSetProductReplacement()
    {
        $product = new QuoteProduct();

        $this->assertNull($product->getProductReplacementSku());
        $this->assertNull($product->getFreeFormProductReplacement());

        $product->setProductReplacement((new Product)->setSku('test-sku-replacement'));

        $this->assertEquals('test-sku-replacement', $product->getProductReplacementSku());
        $this->assertEquals(
            (string)(new Product)->setSku('test-sku-replacement'),
            $product->getFreeFormProductReplacement()
        );
    }

    public function testAddQuoteProductOffer()
    {
        $quoteProduct = new QuoteProduct();
        $quoteProductOffer = new QuoteProductOffer();

        $this->assertNull($quoteProductOffer->getQuoteProduct());

        $quoteProduct->addQuoteProductOffer($quoteProductOffer);

        $this->assertEquals($quoteProduct, $quoteProductOffer->getQuoteProduct());
    }

    public function testAddQuoteProductRequest()
    {
        $quoteProduct = new QuoteProduct();
        $quoteProductRequest = new QuoteProductRequest();

        $this->assertNull($quoteProductRequest->getQuoteProduct());

        $quoteProduct->addQuoteProductRequest($quoteProductRequest);

        $this->assertEquals($quoteProduct, $quoteProductRequest->getQuoteProduct());
    }

    public function testGetTypeTitles()
    {
        $this->assertEquals(
            [
                QuoteProduct::TYPE_OFFER => 'offer',
                QuoteProduct::TYPE_REQUESTED => 'requested',
                QuoteProduct::TYPE_NOT_AVAILABLE => 'not_available',
            ],
            QuoteProduct::getTypes()
        );
    }

    public function testIsTypeOffer()
    {
        $quoteProduct = new QuoteProduct();
        $quoteProduct->setType(QuoteProduct::TYPE_OFFER);

        $this->assertTrue($quoteProduct->isTypeOffer());
    }

    public function testIsTypeRequested()
    {
        $quoteProduct = new QuoteProduct();
        $quoteProduct->setType(QuoteProduct::TYPE_REQUESTED);

        $this->assertTrue($quoteProduct->isTypeRequested());
    }

    public function testIsTypeNotAvailable()
    {
        $quoteProduct = new QuoteProduct();
        $quoteProduct->setType(QuoteProduct::TYPE_NOT_AVAILABLE);

        $this->assertTrue($quoteProduct->isTypeNotAvailable());
    }
}
