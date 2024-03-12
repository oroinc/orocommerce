<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Entity;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product as ProductStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductKitItemLineItem;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Entity\QuoteProductRequest;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class QuoteProductTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
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

        $entity = new QuoteProduct();
        self::assertPropertyAccessors($entity, $properties);

        self::assertPropertyCollections($entity, [
            ['quoteProductOffers', new QuoteProductOffer()],
            ['quoteProductRequests', new QuoteProductRequest()],
        ]);
    }

    public function testKitItemLineItems(): void
    {
        $entity = new QuoteProduct();

        $productKitItem = new ProductKitItemStub(42);
        $kitItemLineItem = (new QuoteProductKitItemLineItem())
            ->setKitItem($productKitItem);

        self::assertSame([], $entity->getKitItemLineItems()->toArray());

        $entity->addKitItemLineItem($kitItemLineItem);
        self::assertSame(
            [$productKitItem->getId() => $kitItemLineItem],
            $entity->getKitItemLineItems()->toArray()
        );

        $entity->removeKitItemLineItem($kitItemLineItem);
        self::assertSame([], $entity->getKitItemLineItems()->toArray());
    }

    public function testGetEntityIdentifier(): void
    {
        $product = new QuoteProduct();
        $value = 321;
        ReflectionUtil::setId($product, $value);
        self::assertSame($value, $product->getEntityIdentifier());
    }

    public function testUpdateProducts(): void
    {
        $product = (new ProductStub())->setSku('product-sku');
        $replacement = (new ProductStub())->setSku('replacement-sku');

        $quoteProduct = new QuoteProduct();

        self::assertNull($quoteProduct->getProductSku());
        self::assertNull($quoteProduct->getFreeFormProduct());
        self::assertNull($quoteProduct->getProductReplacementSku());
        self::assertNull($quoteProduct->getFreeFormProductReplacement());

        ReflectionUtil::setPropertyValue($quoteProduct, 'product', $product);
        ReflectionUtil::setPropertyValue($quoteProduct, 'productReplacement', $replacement);

        $quoteProduct->updateProducts();

        self::assertSame('product-sku', $quoteProduct->getProductSku());
        self::assertSame((string)$product, $quoteProduct->getFreeFormProduct());
        self::assertSame('replacement-sku', $quoteProduct->getProductReplacementSku());
        self::assertSame((string)$replacement, $quoteProduct->getFreeFormProductReplacement());
    }

    public function testSetProduct(): void
    {
        $product = new QuoteProduct();

        self::assertNull($product->getProductSku());
        self::assertNull($product->getFreeFormProduct());

        $product->setProduct((new ProductStub())->setSku('test-sku'));

        self::assertEquals('test-sku', $product->getProductSku());
        self::assertEquals((string)(new ProductStub())->setSku('test-sku'), $product->getFreeFormProduct());
    }

    public function testSetProductReplacement(): void
    {
        $product = new QuoteProduct();

        self::assertNull($product->getProductReplacementSku());
        self::assertNull($product->getFreeFormProductReplacement());

        $product->setProductReplacement((new ProductStub())->setSku('test-sku-replacement'));

        self::assertEquals('test-sku-replacement', $product->getProductReplacementSku());
        self::assertEquals(
            (string)(new ProductStub())->setSku('test-sku-replacement'),
            $product->getFreeFormProductReplacement()
        );
    }

    public function testAddQuoteProductOffer(): void
    {
        $quoteProduct = new QuoteProduct();
        $quoteProductOffer = new QuoteProductOffer();

        self::assertNull($quoteProductOffer->getQuoteProduct());

        $quoteProduct->addQuoteProductOffer($quoteProductOffer);

        self::assertEquals($quoteProduct, $quoteProductOffer->getQuoteProduct());
    }

    public function testAddQuoteProductRequest(): void
    {
        $quoteProduct = new QuoteProduct();
        $quoteProductRequest = new QuoteProductRequest();

        self::assertNull($quoteProductRequest->getQuoteProduct());

        $quoteProduct->addQuoteProductRequest($quoteProductRequest);

        self::assertEquals($quoteProduct, $quoteProductRequest->getQuoteProduct());
    }

    public function testGetTypeTitles(): void
    {
        self::assertEquals(
            [
                QuoteProduct::TYPE_OFFER => 'offer',
                QuoteProduct::TYPE_REQUESTED => 'requested',
                QuoteProduct::TYPE_NOT_AVAILABLE => 'not_available',
            ],
            QuoteProduct::getTypes()
        );
    }

    public function testIsTypeOffer(): void
    {
        $quoteProduct = new QuoteProduct();
        $quoteProduct->setType(QuoteProduct::TYPE_OFFER);

        self::assertTrue($quoteProduct->isTypeOffer());
    }

    public function testIsTypeRequested(): void
    {
        $quoteProduct = new QuoteProduct();
        $quoteProduct->setType(QuoteProduct::TYPE_REQUESTED);

        self::assertTrue($quoteProduct->isTypeRequested());
    }

    public function testIsTypeNotAvailable(): void
    {
        $quoteProduct = new QuoteProduct();
        $quoteProduct->setType(QuoteProduct::TYPE_NOT_AVAILABLE);

        self::assertTrue($quoteProduct->isTypeNotAvailable());
    }

    /**
     * @dataProvider hasQuoteProductOfferByPriceTypeDataProvider
     */
    public function testHasQuoteProductOfferByPriceType(array $offers, int $type, bool $expected): void
    {
        $quoteProduct = new QuoteProduct();
        foreach ($offers as $offer) {
            $quoteProduct->addQuoteProductOffer($offer);
        }

        self::assertSame($expected, $quoteProduct->hasQuoteProductOfferByPriceType($type));
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
    public function testHasIncrementalOffers(array $offers, bool $expected): void
    {
        $quoteProduct = new QuoteProduct();
        foreach ($offers as $offer) {
            $quoteProduct->addQuoteProductOffer($offer);
        }

        self::assertSame($expected, $quoteProduct->hasIncrementalOffers());
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
    public function testIsProductFreeForm(array $inputData, bool $expectedResult): void
    {
        $quoteProduct = new QuoteProduct();

        $quoteProduct
            ->setFreeFormProduct($inputData['title'])
            ->setProduct($inputData['product']);

        self::assertEquals($expectedResult, $quoteProduct->isProductFreeForm());
    }

    /**
     * @dataProvider freeFormProvider
     */
    public function testIsProductReplacementFreeForm(array $inputData, bool $expectedResult): void
    {
        $quoteProduct = new QuoteProduct();

        $quoteProduct
            ->setFreeFormProductReplacement($inputData['title'])
            ->setProductReplacement($inputData['product']);

        self::assertEquals($expectedResult, $quoteProduct->isProductReplacementFreeForm());
    }

    /**
     * @dataProvider getProductNameProvider
     */
    public function testGetProductName(array $inputData, string $expectedResult): void
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

        self::assertEquals($expectedResult, $quoteProduct->getProductName());
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
        $product1->expects(self::any())
            ->method('__toString')
            ->willReturn('Product 1');
        $product2 = $this->createMock(Product::class);
        $product2->expects(self::any())
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
