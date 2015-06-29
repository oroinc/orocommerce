<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Entity;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;

class QuoteProductTest extends AbstractTest
{
    public function testProperties()
    {
        $properties = [
            ['id', 123],
            ['quote', new Quote()],
            ['product', new Product()],
            ['productSku', 'sku'],
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

    public function testAddQuoteProductOffer()
    {
        $quoteProduct       = new QuoteProduct();
        $quoteProductOffer   = new QuoteProductOffer();

        $this->assertNull($quoteProductOffer->getQuoteProduct());

        $quoteProduct->addQuoteProductOffer($quoteProductOffer);

        $this->assertEquals($quoteProduct, $quoteProductOffer->getQuoteProduct());
    }
}
