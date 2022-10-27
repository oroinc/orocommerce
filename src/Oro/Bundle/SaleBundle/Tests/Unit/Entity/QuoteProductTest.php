<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Entity;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product as ProductStub;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Entity\QuoteProductRequest;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class QuoteProductTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', 123],
            ['quote', new Quote()],
            ['product', new ProductStub()],
            ['freeFormProduct', 'free form product'],
            ['productSku', 'sku'],
            ['productReplacement', new ProductStub()],
            ['freeFormProductReplacement', 'free form product replacement'],
            ['productReplacementSku', 'sku-replacement'],
            ['type', QuoteProduct::TYPE_OFFER],
            ['comment', 'Seller notes'],
            ['commentCustomer', 'Customer notes'],
        ];

        self::assertPropertyAccessors(new QuoteProduct(), $properties);

        self::assertPropertyCollections(new QuoteProduct(), [
            ['quoteProductOffers', new QuoteProductOffer()],
            ['quoteProductRequests', new QuoteProductRequest()],
        ]);
    }

    public function testGetEntityIdentifier()
    {
        $product = new QuoteProduct();
        $value = 321;
        ReflectionUtil::setId($product, $value);
        $this->assertSame($value, $product->getEntityIdentifier());
    }

    public function testUpdateProducts()
    {
        $product = (new ProductStub())->setSku('product-sku');
        $replacement = (new ProductStub())->setSku('replacement-sku');

        $quoteProduct = new QuoteProduct();

        $this->assertNull($quoteProduct->getProductSku());
        $this->assertNull($quoteProduct->getFreeFormProduct());
        $this->assertNull($quoteProduct->getProductReplacementSku());
        $this->assertNull($quoteProduct->getFreeFormProductReplacement());

        ReflectionUtil::setPropertyValue($quoteProduct, 'product', $product);
        ReflectionUtil::setPropertyValue($quoteProduct, 'productReplacement', $replacement);

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

        $product->setProduct((new ProductStub())->setSku('test-sku'));

        $this->assertEquals('test-sku', $product->getProductSku());
        $this->assertEquals((string)(new ProductStub())->setSku('test-sku'), $product->getFreeFormProduct());
    }

    public function testSetProductReplacement()
    {
        $product = new QuoteProduct();

        $this->assertNull($product->getProductReplacementSku());
        $this->assertNull($product->getFreeFormProductReplacement());

        $product->setProductReplacement((new ProductStub())->setSku('test-sku-replacement'));

        $this->assertEquals('test-sku-replacement', $product->getProductReplacementSku());
        $this->assertEquals(
            (string)(new ProductStub())->setSku('test-sku-replacement'),
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
     * @dataProvider hasQuoteProductOfferByPriceTypeDataProvider
     */
    public function testHasQuoteProductOfferByPriceType(array $offers, int $type, bool $expected)
    {
        $quoteProduct = new QuoteProduct();
        foreach ($offers as $offer) {
            $quoteProduct->addQuoteProductOffer($offer);
        }

        $this->assertSame($expected, $quoteProduct->hasQuoteProductOfferByPriceType($type));
    }

    public function hasQuoteProductOfferByPriceTypeDataProvider(): array
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
     * @dataProvider hasIncrementalOffersDataProvider
     */
    public function testHasIncrementalOffers(array $offers, bool $expected)
    {
        $quoteProduct = new QuoteProduct();
        foreach ($offers as $offer) {
            $quoteProduct->addQuoteProductOffer($offer);
        }

        $this->assertSame($expected, $quoteProduct->hasIncrementalOffers());
    }

    public function hasIncrementalOffersDataProvider(): array
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
     * @dataProvider freeFormProvider
     */
    public function testIsProductFreeForm(array $inputData, bool $expectedResult)
    {
        $quoteProduct = new QuoteProduct();

        $quoteProduct
            ->setFreeFormProduct($inputData['title'])
            ->setProduct($inputData['product']);

        $this->assertEquals($expectedResult, $quoteProduct->isProductFreeForm());
    }

    /**
     * @dataProvider freeFormProvider
     */
    public function testIsProductReplacementFreeForm(array $inputData, bool $expectedResult)
    {
        $quoteProduct = new QuoteProduct();

        $quoteProduct
            ->setFreeFormProductReplacement($inputData['title'])
            ->setProductReplacement($inputData['product']);

        $this->assertEquals($expectedResult, $quoteProduct->isProductReplacementFreeForm());
    }

    /**
     * @dataProvider getProductNameProvider
     */
    public function testGetProductName(array $inputData, string $expectedResult)
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

    public function freeFormProvider(): array
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
                    'product' => new ProductStub(),
                    'title' => null,
                ],
                'expected' => false,
            ],
            'product & !product title2' => [
                'input' => [
                    'product' => new ProductStub(),
                    'title' => '',
                ],
                'expected' => false,
            ],
            'product & product title' => [
                'input' => [
                    'product' => new ProductStub(),
                    'title' => 'free form title',
                ],
                'expected' => false,
            ],
        ];
    }

    public function getProductNameProvider(): array
    {
        $product1 = $this->createMock(Product::class);
        $product1->expects($this->any())
            ->method('__toString')
            ->willReturn('Product 1');
        $product2 = $this->createMock(Product::class);
        $product2->expects($this->any())
            ->method('__toString')
            ->willReturn('Product 2');

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
