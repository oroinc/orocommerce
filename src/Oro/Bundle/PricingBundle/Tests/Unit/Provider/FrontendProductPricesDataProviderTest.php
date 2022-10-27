<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\PricingBundle\Provider\FrontendProductPricesDataProvider;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItem;
use Oro\Component\Testing\Unit\EntityTrait;

class FrontendProductPricesDataProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const TEST_CURRENCY = 'USD';

    /**
     * @var FrontendProductPricesDataProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ProductPriceProviderInterface
     */
    protected $productPriceProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|UserCurrencyManager
     */
    protected $userCurrencyManager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ProductPriceScopeCriteriaRequestHandler
     */
    protected $scopeCriteriaRequestHandler;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->productPriceProvider = $this->createMock(ProductPriceProviderInterface::class);
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);
        $this->scopeCriteriaRequestHandler = $this->createMock(ProductPriceScopeCriteriaRequestHandler::class);

        $this->provider = new FrontendProductPricesDataProvider(
            $this->productPriceProvider,
            $this->userCurrencyManager,
            $this->scopeCriteriaRequestHandler
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        unset(
            $this->productPriceProvider,
            $this->userCurrencyManager,
            $this->scopeCriteriaRequestHandler,
            $this->scopeCriteriaRequestHandle,
            $this->provider
        );
    }

    /**
     * @dataProvider getDataDataProvider
     *
     * @param ProductPriceCriteria[] $criteriaArray
     * @param array $matchedPrices
     * @param array $lineItems
     * @param array $expectedResult
     */
    public function testGetProductsPrices(
        array $criteriaArray,
        array $matchedPrices,
        array $lineItems,
        array $expectedResult
    ) {
        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->scopeCriteriaRequestHandler
            ->expects($this->any())
            ->method('getPriceScopeCriteria')
            ->willReturn($scopeCriteria);

        $this->userCurrencyManager
            ->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn(self::TEST_CURRENCY);

        $this->productPriceProvider->expects($this->once())
            ->method('getMatchedPrices')
            ->with($criteriaArray, $scopeCriteria)
            ->willReturn($matchedPrices);

        $result = $this->provider->getProductsMatchedPrice($lineItems);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function getDataDataProvider()
    {
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 42]);
        $productUnit = new ProductUnit();
        $productUnit->setCode('test');
        $quantity = 100;

        $lineItemWithProduct = new ProductLineItem('test');
        $lineItemWithProduct->setProduct($product);
        $lineItemWithProduct->setUnit($productUnit);
        $lineItemWithProduct->setQuantity($quantity);

        $lineItemWOProduct = new ProductLineItem('test');
        $lineItemWOProduct->setUnit($productUnit);
        $lineItemWOProduct->setQuantity($quantity);

        $criteria = new ProductPriceCriteria($product, $productUnit, $quantity, self::TEST_CURRENCY);

        $price = new Price();
        $price->setValue('123');
        $price->setCurrency(self::TEST_CURRENCY);

        return [
            'line item with product' => [
                'criteriaArray' => [$criteria],
                'matchedPrices' => [
                    '42-test-100-USD' => $price
                ],
                'lineItems' => [$lineItemWithProduct],
                'expectedResult' => [42 => ['test' => $price]]
            ],
            'line item without product' => [
                'criteriaArray' => [],
                'matchedPrices' => [],
                'lineItems' => [$lineItemWOProduct],
                'expectedResult' => []
            ],
        ];
    }

    /**
     * @dataProvider getProductsAllPricesProvider
     *
     * @param ProductHolderInterface[] $lineItems
     * @param Product[] $products
     * @param array $prices
     * @param array $expectedPrices
     */
    public function testGetProductsAllPrices(array $lineItems, array $products, array $prices, array $expectedPrices)
    {
        $scopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->scopeCriteriaRequestHandler
            ->expects($this->any())
            ->method('getPriceScopeCriteria')
            ->willReturn($scopeCriteria);

        $this->userCurrencyManager
            ->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn(self::TEST_CURRENCY);

        $this->productPriceProvider->expects($this->once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->with($scopeCriteria, $products, [self::TEST_CURRENCY])
            ->willReturn($prices);

        $result = $this->provider->getProductsAllPrices($lineItems);
        $this->assertEquals($expectedPrices, $result);
    }

    /**
     * @return array
     */
    public function getProductsAllPricesProvider()
    {
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 42]);
        $productUnit = new ProductUnit();
        $productUnit->setCode('item');

        $quantity = 100;
        $priceValue = 10;

        $lineItemWithProduct = new ProductLineItem('test');
        $lineItemWithProduct->setProduct($product);
        $lineItemWithProduct->setUnit($productUnit);
        $lineItemWithProduct->setQuantity($quantity);

        $lineItemWOProduct = new ProductLineItem('test');
        $lineItemWOProduct->setUnit($productUnit);
        $lineItemWOProduct->setQuantity($quantity);

        return [
            'line item with product' => [
                'lineItems' => [$lineItemWithProduct],
                'products' => [$product],
                'prices' => [
                    42 => $this->getPricesArray($priceValue, $quantity, self::TEST_CURRENCY, ['item'])
                ],
                'expectedPrices' => [
                    42 => [
                        'item' => [$this->createPrice($priceValue, self::TEST_CURRENCY, $quantity, 'item')]
                    ]
                ]
            ],
            'line item without product' => [
                'lineItems' => [$lineItemWOProduct],
                'products' => [],
                'prices' => [],
                'expectedPrices' => []
            ]
        ];
    }

    /**
     * @param float $price
     * @param int $quantity
     * @param string $currency
     * @param array $unitCodes
     * @return array
     */
    private function getPricesArray($price, $quantity, $currency, array $unitCodes)
    {
        return array_map(function ($unitCode) use ($price, $quantity, $currency) {
            return $this->createPrice($price, $currency, $quantity, $unitCode);
        }, $unitCodes);
    }

    /**
     * @param float $price
     * @param int $quantity
     * @param string $currency
     * @param string $unitCode
     * @return ProductPriceDTO
     */
    private function createPrice($price, $currency, $quantity, $unitCode)
    {
        return new ProductPriceDTO(
            $this->getEntity(Product::class, ['id' => 1]),
            Price::create($price, $currency),
            $quantity,
            $this->getEntity(ProductUnit::class, ['code' => $unitCode])
        );
    }
}
