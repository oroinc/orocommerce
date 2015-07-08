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
                QuoteProduct::TYPE_OFFER => 'orob2b.sale.quoteproduct.type.offer',
                QuoteProduct::TYPE_REQUESTED => 'orob2b.sale.quoteproduct.type.requested',
                QuoteProduct::TYPE_NOT_AVAILABLE => 'orob2b.sale.quoteproduct.type.not_available',
            ],
            QuoteProduct::getTypeTitles()
        );
    }

    public function testGetTypeTitle()
    {
        $quoteProduct = new QuoteProduct();
        $quoteProduct->setType(QuoteProduct::TYPE_OFFER);

        $this->assertEquals($quoteProduct->getTypeTitle(), 'orob2b.sale.quoteproduct.type.offer');
    }
}
