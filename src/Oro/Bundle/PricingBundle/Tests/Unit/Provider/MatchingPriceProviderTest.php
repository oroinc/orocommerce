<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Provider\MatchingPriceProvider;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Component\Testing\Unit\EntityTrait;

class MatchingPriceProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ProductPriceProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $productPriceProvider;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var MatchingPriceProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->productPriceProvider = $this->createMock(ProductPriceProviderInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->provider = new MatchingPriceProvider(
            $this->productPriceProvider,
            $this->doctrineHelper,
            Product::class,
            ProductUnit::class
        );
    }

    public function testGetMatchingPrices()
    {
        $priceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);

        $productId = 1;
        $productUnitCode = 'unitCode';
        $qty = 5.5;
        $currency = 'USD';

        $lineItems = [
            [
                'product' => $productId,
                'unit' => $productUnitCode,
                'qty' => $qty,
                'currency' => $currency,
            ]
        ];

        $product = $this->getEntity(Product::class, ['id' => $productId]);

        $productUnit = $this->getEntity(ProductUnit::class, ['code' => $productUnitCode]);

        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityReference')
            ->withConsecutive(
                [Product::class, $productId],
                [ProductUnit::class, $productUnitCode]
            )
            ->willReturnOnConsecutiveCalls($product, $productUnit);

        $expectedMatchedPrices = [
            'price1' => Price::create(10, 'USD'),
            'price2' => Price::create(15, 'USD'),
            'price3' => Price::create(20, 'EUR'),
        ];
        $this->productPriceProvider->expects($this->once())
            ->method('getMatchedPrices')
            ->with([new ProductPriceCriteria($product, $productUnit, $qty, $currency)], $priceScopeCriteria)
            ->willReturn($expectedMatchedPrices);

        $this->assertEquals(
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
