<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Entity;

use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Entity\QuoteProductRequest;

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

    /**
     * @param array $offers
     * @param bool $expected
     * @dataProvider hasIncrementalOffersDataProvider
     */
    public function testHasIncrementalOffers(array $offers, $expected)
    {
        $quoteProduct = new QuoteProduct();
        foreach ($offers as $offer) {
            $quoteProduct->addQuoteProductOffer($offer);
        }

        $this->assertSame($expected, $quoteProduct->hasIncrementalOffers());
    }

    /**
     * @return array
     */
    public function hasIncrementalOffersDataProvider()
    {
        $firstNotIncrementedOffer = new QuoteProductOffer();
        $firstNotIncrementedOffer->setAllowIncrements(false);

        $secondNotIncrementedOffer = new QuoteProductOffer();
        $secondNotIncrementedOffer->setAllowIncrements(false);

        $incrementedOffer = new QuoteProductOffer();
        $incrementedOffer->setAllowIncrements(true);

        return [
            'no offers' => [
                'offers' => [],
                'expected' => false,
            ],
            'no incremented offers' => [
                'offers' => [$firstNotIncrementedOffer, $secondNotIncrementedOffer],
                'expected' => false,
            ],
            'one incremented offer' => [
                'offers' => [$firstNotIncrementedOffer, $secondNotIncrementedOffer, $incrementedOffer],
                'expected' => true,
            ],
        ];
    }

    /**
     * @param array $inputData
     * @param bool $expectedResult
     *
     * @dataProvider freeFormProvider
     */
    public function testIsProductFreeForm(array $inputData, $expectedResult)
    {
        $quoteProduct = new QuoteProduct();

        $quoteProduct
            ->setFreeFormProduct($inputData['title'])
            ->setProduct($inputData['product'])
        ;

        $this->assertEquals($expectedResult, $quoteProduct->isProductFreeForm());
    }

    /**
     * @param array $inputData
     * @param bool $expectedResult
     *
     * @dataProvider freeFormProvider
     */
    public function testIsProductReplacementFreeForm(array $inputData, $expectedResult)
    {
        $quoteProduct = new QuoteProduct();

        $quoteProduct
            ->setFreeFormProductReplacement($inputData['title'])
            ->setProductReplacement($inputData['product'])
        ;

        $this->assertEquals($expectedResult, $quoteProduct->isProductReplacementFreeForm());
    }

    /**
     * @param array $inputData
     * @param string $expectedResult
     *
     * @dataProvider getProductNameProvider
     */
    public function testGetProductName(array $inputData, $expectedResult)
    {
        $quoteProduct = new QuoteProduct();

        if ($inputData['isProductReplacement']) {
            $quoteProduct
                ->setType(QuoteProduct::TYPE_NOT_AVAILABLE)
                ->setFreeFormProductReplacement($inputData['productTitle'])
                ->setProductReplacement($inputData['product']);
        } else {
            $quoteProduct
                ->setFreeFormProduct($inputData['productTitle'])
                ->setProduct($inputData['product']);
        }

        $this->assertEquals($expectedResult, $quoteProduct->getProductName());
    }


    /**
     * @return array
     */
    public function freeFormProvider()
    {
        return [
            '!product & !product title' => [
                'input' => [
                    'product' => null,
                    'title' => null,
                ],
                'expected' => false,
            ],
            '!product & product title' => [
                'input' => [
                    'product' => null,
                    'title' => 'free form title',
                ],
                'expected' => true,
            ],
            '!product & product title2' => [
                'input' => [
                    'product' => null,
                    'title' => '0',
                ],
                'expected' => true,
            ],
            'product & !product title' => [
                'input' => [
                    'product' => new Product(),
                    'title' => null,
                ],
                'expected' => false,
            ],
            'product & !product title2' => [
                'input' => [
                    'product' => new Product(),
                    'title' => '',
                ],
                'expected' => false,
            ],
            'product & product title' => [
                'input' => [
                    'product' => new Product(),
                    'title' => 'free form title',
                ],
                'expected' => false,
            ],
        ];
    }

    /**
     * @return array
     */
    public function getProductNameProvider()
    {
        $product1 = $this->getMock('Oro\Bundle\ProductBundle\Entity\Product');
        $product1->expects($this->any())
            ->method('__toString')
            ->willReturn('Product 1');
        ;
        $product2 = $this->getMock('Oro\Bundle\ProductBundle\Entity\Product');
        $product2->expects($this->any())
            ->method('__toString')
            ->willReturn('Product 2');
        ;

        return [
            'no products' => [
                'input' => [
                    'product' => null,
                    'isProductReplacement' => false,
                    'productTitle' => null,
                    'productReplacementTitle' => null,
                ],
                'expected' => '',
            ],
            'product' => [
                'input' => [
                    'product' => $product1,
                    'isProductReplacement' => false,
                    'productTitle' => null,
                ],
                'expected' => 'Product 1',
            ],
            'productReplacement' => [
                'input' => [
                    'product' => $product2,
                    'isProductReplacement' => true,
                    'productTitle' => null,
                ],
                'expected' => 'Product 2',
            ],
            'product free form' => [
                'input' => [
                    'product' => null,
                    'isProductReplacement' => false,
                    'productTitle' => 'Free Form Product 1',
                ],
                'expected' => 'Free Form Product 1',
            ],
            'productReplacement free form' => [
                'input' => [
                    'product' => null,
                    'isProductReplacement' => true,
                    'productTitle' => 'Free Form Product 2',
                ],
                'expected' => 'Free Form Product 2',
            ],
        ];
    }
}
