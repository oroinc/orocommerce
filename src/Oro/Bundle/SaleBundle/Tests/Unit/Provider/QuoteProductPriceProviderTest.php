<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Provider\QuoteProductPriceProvider;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;

class QuoteProductPriceProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ProductPriceScopeCriteriaFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $priceScopeCriteriaFactory;

    /** @var ProductPriceProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $productPriceProvider;

    /** @var CurrencyProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $currencyProvider;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var QuoteProductPriceProvider */
    private $quoteProductPriceProvider;

    protected function setUp(): void
    {
        $this->productPriceProvider = $this->createMock(ProductPriceProviderInterface::class);
        $this->priceScopeCriteriaFactory = $this->createMock(ProductPriceScopeCriteriaFactoryInterface::class);
        $this->currencyProvider = $this->createMock(CurrencyProviderInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->quoteProductPriceProvider = new QuoteProductPriceProvider(
            $this->productPriceProvider,
            $this->priceScopeCriteriaFactory,
            $this->currencyProvider,
            $this->doctrineHelper,
            $this->aclHelper
        );
    }

    /**
     * @dataProvider getTierPricesDataProvider
     */
    public function testGetTierPrices(array $quoteProducts, ?array $products, int $tierPricesCount)
    {
        $website = new Website();
        $customer = new Customer();

        $quote = new Quote();
        $quote->setWebsite($website)->setCustomer($customer);

        $currencies = ['USD', 'EUR'];
        $this->currencyProvider->expects($this->any())
            ->method('getCurrencyList')
            ->willReturn($currencies);

        foreach ($quoteProducts as $quoteProduct) {
            $quote->addQuoteProduct($quoteProduct);
        }

        if ($products) {
            $productScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
            $this->priceScopeCriteriaFactory->expects($this->once())
                ->method('createByContext')
                ->with($quote)
                ->willReturn($productScopeCriteria);
            $this->productPriceProvider->expects($this->once())
                ->method('getPricesByScopeCriteriaAndProducts')
                ->with($productScopeCriteria, $products)
                ->willReturn(range(0, $tierPricesCount - 1), $currencies);
        } else {
            $this->productPriceProvider->expects($this->never())
                ->method('getPricesByScopeCriteriaAndProducts');
        }

        $result = $this->quoteProductPriceProvider->getTierPrices($quote);

        $this->assertIsArray($result);
        $this->assertCount($tierPricesCount, $result);
    }

    /**
     * @dataProvider getTierPricesDataProvider
     */
    public function testGetTierPricesForProducts(array $quoteProducts, ?array $products, int $tierPricesCount)
    {
        $website = new Website();
        $customer = new Customer();

        $quote = new Quote();
        $quote->setWebsite($website)->setCustomer($customer);

        $currencies = ['USD', 'EUR'];
        $this->currencyProvider->expects($this->any())
            ->method('getCurrencyList')
            ->willReturn($currencies);

        if ($products) {
            $productScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
            $this->priceScopeCriteriaFactory->expects($this->once())
                ->method('createByContext')
                ->with($quote)
                ->willReturn($productScopeCriteria);
            $this->productPriceProvider->expects($this->once())
                ->method('getPricesByScopeCriteriaAndProducts')
                ->with($productScopeCriteria, $products)
                ->willReturn(range(0, $tierPricesCount - 1), $currencies);
        } else {
            $this->productPriceProvider->expects($this->never())
                ->method('getPricesByScopeCriteriaAndProducts');
        }

        $result = $this->quoteProductPriceProvider->getTierPricesForProducts(
            $quote,
            array_filter(
                array_map(
                    function (QuoteProduct $quoteProduct) {
                        return $quoteProduct->getProduct();
                    },
                    $quoteProducts
                )
            )
        );

        $this->assertIsArray($result);
        $this->assertCount($tierPricesCount, $result);
    }

    public function getTierPricesDataProvider(): array
    {
        $quoteProduct = $this->getQuoteProduct();
        $emptyQuoteProduct = $this->getQuoteProduct('empty');

        $product = $quoteProduct->getProduct();

        return [
            'quote product with product' => [
                'quoteProducts' => [$quoteProduct],
                'products' => [$product],
                'tierPricesCount' => 1,
            ],
            'quote product without product' => [
                'quoteProducts' => [$emptyQuoteProduct],
                'products' => null,
                'tierPricesCount' => 0,
            ],
            'empty quote products' => [
                'quoteProducts' => [],
                'products' => null,
                'tierPricesCount' => 0,
            ],
        ];
    }

    /**
     * @dataProvider getMatchedPricesDataProvider
     */
    public function testGetMatchedPrices(
        array $quoteProducts,
        ?array $productPriceCriteria,
        array $prices,
        array $expectedResult
    ) {
        $quote = new Quote();
        $website = new Website();
        $customer = new Customer();
        $quote->setWebsite($website)->setCustomer($customer);

        foreach ($quoteProducts as $quoteProduct) {
            $quote->addQuoteProduct($quoteProduct);
        }

        if ($productPriceCriteria) {
            $productScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
            $this->priceScopeCriteriaFactory->expects($this->once())
                ->method('createByContext')
                ->with($quote)
                ->willReturn($productScopeCriteria);
            $this->productPriceProvider->expects($this->once())
                ->method('getMatchedPrices')
                ->with($productPriceCriteria, $productScopeCriteria)
                ->willReturn($prices);
        } else {
            $this->productPriceProvider->expects($this->never())
                ->method('getMatchedPrices');
        }

        $result = $this->quoteProductPriceProvider->getMatchedPrices($quote);

        $this->assertEquals($expectedResult, $result);
    }

    public function getMatchedPricesDataProvider(): array
    {
        $quoteProduct = $this->getQuoteProduct();
        $emptyQuoteProduct = $this->getQuoteProduct('empty');

        $product1 = $quoteProduct->getProduct();

        $quoteProductOffer1 = $quoteProduct->getQuoteProductOffers()->get(0);
        $quoteProductOffer2 = $quoteProduct->getQuoteProductOffers()->get(1);

        $productsPriceCriteria = [
            new ProductPriceCriteria(
                $product1,
                $quoteProductOffer1->getProductUnit(),
                $quoteProductOffer1->getQuantity(),
                $quoteProductOffer1->getPrice()->getCurrency()
            ),
        ];
        $productsPriceCriteria[] = new ProductPriceCriteria(
            $product1,
            $quoteProductOffer2->getProductUnit(),
            $quoteProductOffer2->getQuantity(),
            $quoteProductOffer2->getPrice()->getCurrency()
        );

        return [
            'quote product with product' => [
                'quoteProducts' => [$quoteProduct],
                'productPriceCriteria' => $productsPriceCriteria,
                'prices' => [
                    1 => Price::create(10, 'USD')
                ],
                'expectedResult' => [
                    1 => [
                        'value' => 10,
                        'currency' => 'USD'
                    ]
                ]
            ],
            'quote product with product and empty matched price' => [
                'quoteProducts' => [$quoteProduct],
                'productPriceCriteria' => $productsPriceCriteria,
                'prices' => [
                    1 => null
                ],
                'expectedResult' => [
                    1 => null
                ]
            ],
            'quote product without product' => [
                'quoteProducts' => [$emptyQuoteProduct],
                'productPriceCriteria' => null,
                'prices' => [],
                'expectedResult' => []
            ],
            'empty quote products' => [
                'quoteProducts' => [],
                'productPriceCriteria' => null,
                'prices' => [],
                'expectedResult' => []
            ],
        ];
    }

    private function getQuoteProduct(string $type = ''): QuoteProduct
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode('kg');

        $price = new Price();
        $price->setCurrency('USD');

        $quoteProductOffer = new QuoteProductOffer();
        $quoteProductOffer->setProductUnit($productUnit);
        $quoteProductOffer->setQuantity(1);
        $quoteProductOffer->setPrice($price);

        $quoteProductOffer2 = new QuoteProductOffer();
        $quoteProductOffer2->setQuantity(2);

        $quoteProductOffer3 = new QuoteProductOffer();
        $quoteProductOffer3->setProductUnit($productUnit);

        /** @var Product $product1 */
        $product1 = $this->getEntity(Product::class, ['id' => 1]);

        switch ($type) {
            case 'empty':
                $quoteProduct = new QuoteProduct();
                break;
            default:
                $quoteProduct = new QuoteProduct();
                $quoteProduct->setProduct($product1);
                $quoteProduct->addQuoteProductOffer($quoteProductOffer);
                $quoteProduct->addQuoteProductOffer(clone($quoteProductOffer));
                $quoteProduct->addQuoteProductOffer($quoteProductOffer2);
                $quoteProduct->addQuoteProductOffer($quoteProductOffer3);
                break;
        }

        return $quoteProduct;
    }

    public function testHasEmptyPriceTrue()
    {
        $quote = new Quote();
        $quoteProduct = $this->getQuoteProduct();

        $quote->addQuoteProduct($quoteProduct);
        $this->assertTrue($this->quoteProductPriceProvider->hasEmptyPrice($quote));
    }

    public function testHasEmptyPriceFalse()
    {
        $quote = new Quote();
        $quoteProduct = new QuoteProduct();

        $productUnit = new ProductUnit();
        $productUnit->setCode('kg');

        $price = new Price();
        $price->setCurrency('USD');
        $price->setValue(12.345);

        $quoteProductOffer = new QuoteProductOffer();
        $quoteProductOffer->setProductUnit($productUnit);
        $quoteProductOffer->setQuantity(1);
        $quoteProductOffer->setPrice($price);
        $quoteProduct->addQuoteProductOffer($quoteProductOffer);

        $quote->addQuoteProduct($quoteProduct);
        $this->assertFalse($this->quoteProductPriceProvider->hasEmptyPrice($quote));
    }

    /**
     * @dataProvider getMatchedProductPriceProvider
     */
    public function testGetMatchedProductPrice(array $matchedPrices, Price $expectedResult = null)
    {
        $quote = new Quote();

        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 12, 'sku' => 'psku']);
        /** @var ProductUnit $unit */
        $unit = $this->getEntity(ProductUnit::class, ['code' => 'punit']);

        $productRepository = $this->createMock(ProductRepository::class);
        $unitRepository = $this->createMock(ProductUnitRepository::class);
        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityRepository')
            ->withConsecutive(
                [\Oro\Bundle\ProductBundle\Entity\Product::class],
                [ProductUnit::class]
            )
            ->willReturnOnConsecutiveCalls(
                $productRepository,
                $unitRepository
            );

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($product);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $productRepository->expects($this->once())
            ->method('getBySkuQueryBuilder')
            ->with('psku')
            ->willReturn($queryBuilder);
        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);
        $unitRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['code' => 'punit'])
            ->willReturn($unit);

        $productPriceCriteria = new ProductPriceCriteria(
            $product,
            $unit,
            32,
            'USD'
        );

        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->priceScopeCriteriaFactory->expects($this->once())
            ->method('createByContext')
            ->with($quote)
            ->willReturn($scopeCriteria);

        $this->productPriceProvider->expects($this->once())
            ->method('getMatchedPrices')
            ->with([$productPriceCriteria], $scopeCriteria)
            ->willReturn($matchedPrices);

        $result = $this->quoteProductPriceProvider->getMatchedProductPrice(
            $quote,
            'psku',
            'punit',
            32,
            'USD'
        );

        $this->assertEquals($expectedResult, $result);
    }

    public function getMatchedProductPriceProvider(): array
    {
        $expectedPrice = Price::create(9.99, 'USD');

        return [
            'Matched price found' => [
                [
                    '12-punit-32-USD' => $expectedPrice,
                ],
                $expectedPrice
            ],
            'Matched price not found' => [
                [
                    '12-punit-32-USD' => null,
                ],
                null
            ],
        ];
    }

    public function testGetMatchedProductPriceNoProductBySku()
    {
        $quote = new Quote();

        $productRepository = $this->createMock(ProductRepository::class);
        $unitRepository = $this->createMock(ProductUnitRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(\Oro\Bundle\ProductBundle\Entity\Product::class)
            ->willReturn($productRepository);

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn(null);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $productRepository->expects($this->once())
            ->method('getBySkuQueryBuilder')
            ->with('psku')
            ->willReturn($queryBuilder);
        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $unitRepository->expects($this->never())
            ->method('findOneBy');

        $this->priceScopeCriteriaFactory->expects($this->never())
            ->method('createByContext');

        $this->productPriceProvider->expects($this->never())
            ->method('getMatchedPrices');

        $result = $this->quoteProductPriceProvider->getMatchedProductPrice(
            $quote,
            'psku',
            'punit',
            32,
            'USD'
        );

        $this->assertNull($result);
    }

    public function testGetMatchedProductPriceNoUnitByCode()
    {
        $quote = new Quote();

        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 12, 'sku' => 'psku']);

        $productRepository = $this->createMock(ProductRepository::class);
        $unitRepository = $this->createMock(ProductUnitRepository::class);
        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityRepository')
            ->withConsecutive(
                [\Oro\Bundle\ProductBundle\Entity\Product::class],
                [ProductUnit::class]
            )
            ->willReturnOnConsecutiveCalls(
                $productRepository,
                $unitRepository
            );

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($product);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $productRepository->expects($this->once())
            ->method('getBySkuQueryBuilder')
            ->with('psku')
            ->willReturn($queryBuilder);
        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $unitRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['code' => 'punit'])
            ->willReturn(null);

        $this->priceScopeCriteriaFactory->expects($this->never())
            ->method('createByContext');

        $this->productPriceProvider->expects($this->never())
            ->method('getMatchedPrices');

        $result = $this->quoteProductPriceProvider->getMatchedProductPrice(
            $quote,
            'psku',
            'punit',
            32,
            'USD'
        );

        $this->assertNull($result);
    }
}
