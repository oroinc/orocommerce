<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Entity;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\SaleBundle\Entity\Quote;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductRequest;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
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

    /**
     * @param QuoteProductOffer[] $offers
     * @param int $type
     * @param bool $expected
     * @dataProvider hasQuoteProductOfferByPriceTypeDataProvider
     */
    public function testHasQuoteProductOfferByPriceType(array $offers, $type, $expected)
    {
        $quoteProduct = new QuoteProduct();
        foreach ($offers as $offer) {
            $quoteProduct->addQuoteProductOffer($offer);
        }

        $this->assertSame($expected, $quoteProduct->hasQuoteProductOfferByPriceType($type));
    }

    /**
     * @return array
     */
    public function hasQuoteProductOfferByPriceTypeDataProvider()
    {
        $unitOffer = new QuoteProductOffer();
        $unitOffer->setPriceType(QuoteProductOffer::PRICE_TYPE_UNIT);

        $firstBundledOffer = new QuoteProductOffer();
        $firstBundledOffer->setPriceType(QuoteProductOffer::PRICE_TYPE_BUNDLED);

        $secondBundledOffer = new QuoteProductOffer();
        $secondBundledOffer->setPriceType(QuoteProductOffer::PRICE_TYPE_BUNDLED);

        return [
            'true' => [
                'offers' => [$unitOffer, $firstBundledOffer, $secondBundledOffer],
                'type' => QuoteProductOffer::PRICE_TYPE_UNIT,
                'expected' => true,
            ],
            'false' => [
                'offers' => [$firstBundledOffer, $secondBundledOffer],
                'type' => QuoteProductOffer::PRICE_TYPE_UNIT,
                'expected' => false,
            ],
        ];
    }
}
