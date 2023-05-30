<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Provider\MatchingPriceProvider;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MatchingPriceProviderTest extends TestCase
{
    use EntityTrait;

    private ProductPriceProviderInterface&MockObject $productPriceProvider;

    private DoctrineHelper&MockObject $doctrineHelper;

    private MatchingPriceProvider $provider;

    private ProductPriceCriteriaFactoryInterface&MockObject $productPriceCriteriaFactory;

    protected function setUp(): void
    {
        $this->productPriceProvider = $this->createMock(ProductPriceProviderInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->productPriceCriteriaFactory = $this->createMock(ProductPriceCriteriaFactoryInterface::class);

        $this->provider = new MatchingPriceProvider(
            $this->productPriceProvider,
            $this->doctrineHelper,
            $this->productPriceCriteriaFactory,
            Product::class,
            ProductUnit::class,
        );
    }

    public function testGetMatchingPrices(): void
    {
        $priceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $productPriceCriteria = $this->createMock(ProductPriceCriteria::class);

        $productId1 = 1;
        $productId2 = 2;
        $productUnitCode = 'unitCode';
        $qty = 5.5;
        $currency = 'USD';

        $lineItems = [
            [
                'product' => $productId1,
                'unit' => $productUnitCode,
                'qty' => $qty,
                'currency' => $currency,
            ],
            [
                'product' => $productId2,
                'unit' => $productUnitCode,
                'qty' => $qty,
                'currency' => $currency,
            ],
        ];

        $product1 = $this->getEntity(Product::class, ['id' => $productId1]);
        $product2 = $this->getEntity(Product::class, ['id' => $productId2]);

        $productUnit = $this->getEntity(ProductUnit::class, ['code' => $productUnitCode]);

        $this->productPriceCriteriaFactory
            ->expects(self::exactly(2))
            ->method('create')
            ->willReturnMap([
                [
                    $product1,
                    $productUnit,
                    $qty,
                    $currency,
                    $productPriceCriteria,
                ],
                [
                    $product2,
                    $productUnit,
                    $qty,
                    $currency,
                    null,
                ],
            ]);

        $this->doctrineHelper->expects(self::exactly(4))
            ->method('getEntityReference')
            ->willReturnMap([
                [Product::class, $productId1, $product1],
                [Product::class, $productId2, $product2],
                [ProductUnit::class, $productUnitCode, $productUnit],
            ]);

        $expectedMatchedPrices = [
            'price1' => Price::create(10, 'USD'),
            'price2' => Price::create(15, 'USD'),
            'price3' => Price::create(20, 'EUR'),
        ];
        $this->productPriceProvider->expects(self::once())
            ->method('getMatchedPrices')
            ->with(self::equalTo([$productPriceCriteria]), $priceScopeCriteria)
            ->willReturn($expectedMatchedPrices);

        self::assertEquals(
            $this->formatPrices($expectedMatchedPrices),
            $this->provider->getMatchingPrices($lineItems, $priceScopeCriteria)
        );
    }

    private function formatPrices(array $prices): array
    {
        $formattedPrices = [];
        /** @var Price $value */
        foreach ($prices as $key => $value) {
            $formattedPrices[$key]['value'] = $value->getValue();
            $formattedPrices[$key]['currency'] = $value->getCurrency();
        }

        return $formattedPrices;
    }
}
