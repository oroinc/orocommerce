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
            ['productSku', 'sku'],
            ['productReplacement', new Product()],
            ['productReplacementSku', 'sku-replacement'],
            ['type', QuoteProduct::TYPE_OFFER],
            ['comment', 'Seller notes'],
            ['commentCustomer', 'Customer notes'],
        ];

        static::assertPropertyAccessors(new QuoteProduct(), $properties);

        static::assertPropertyCollections(new QuoteProduct(), [
            ['quoteProductOffers', new QuoteProductOffer()],
            ['quoteProductRequests', new QuoteProductRequest()],
        ]);
    }

    public function testSetProduct()
    {
        $product = new QuoteProduct();

        $this->assertNull($product->getProductSku());

        $product->setProduct((new Product)->setSku('test-sku'));

        $this->assertEquals('test-sku', $product->getProductSku());
    }

    public function testSetProductReplacement()
    {
        $product = new QuoteProduct();

        $this->assertNull($product->getProductSku());

        $product->setProductReplacement((new Product)->setSku('test-sku-replacement'));

        $this->assertEquals('test-sku-replacement', $product->getProductReplacementSku());
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
}
