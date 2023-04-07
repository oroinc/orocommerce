<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemsHolderPriceCriteriaProvider;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemsHolderPricesProvider;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsNotPricedAwareInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductLineItemsHolderPricesProviderTest extends TestCase
{
    private const USD = 'USD';

    private ProductPriceProviderInterface|MockObject $productPriceProvider;

    private ProductPriceScopeCriteriaFactoryInterface|MockObject $priceScopeCriteriaFactory;

    private ProductLineItemsHolderPriceCriteriaProvider|MockObject $lineItemsHolderPriceCriteriaProvider;

    protected function setUp(): void
    {
        $this->productPriceProvider = $this->createMock(ProductPriceProviderInterface::class);
        $this->priceScopeCriteriaFactory = $this->createMock(ProductPriceScopeCriteriaFactoryInterface::class);
        $this->lineItemsHolderPriceCriteriaProvider = $this->createMock(
            ProductLineItemsHolderPriceCriteriaProvider::class
        );

        $this->provider = new ProductLineItemsHolderPricesProvider(
            $this->productPriceProvider,
            $this->priceScopeCriteriaFactory,
            $this->lineItemsHolderPriceCriteriaProvider
        );
    }

    public function testGetMatchedPricesForLineItemsHolderWhenNoProductsPriceCriteria(): void
    {
        $lineItemsHolder = $this->createMock(LineItemsNotPricedAwareInterface::class);
        $this->lineItemsHolderPriceCriteriaProvider
            ->expects(self::once())
            ->method('getProductPriceCriteriaForLineItemsHolder')
            ->with($lineItemsHolder, self::USD)
            ->willReturn([]);

        $priceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->willReturn($priceScopeCriteria);

        $this->productPriceProvider
            ->expects(self::never())
            ->method(self::anything());

        self::assertSame(
            [[], [], $priceScopeCriteria],
            $this->provider->getMatchedPricesForLineItemsHolder($lineItemsHolder, self::USD)
        );
    }

    public function testGetMatchedPricesForLineItemsHolder(): void
    {
        $lineItemsHolder = $this->createMock(LineItemsNotPricedAwareInterface::class);
        $productPriceCriterion1 = $this->createMock(ProductPriceCriteria::class);
        $productPriceCriteria = ['sample_key1' => $productPriceCriterion1];
        $this->lineItemsHolderPriceCriteriaProvider
            ->expects(self::once())
            ->method('getProductPriceCriteriaForLineItemsHolder')
            ->with($lineItemsHolder, self::USD)
            ->willReturn($productPriceCriteria);

        $priceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->willReturn($priceScopeCriteria);

        $prices = ['sample_identifier' => Price::create(12.345, self::USD)];
        $this->productPriceProvider
            ->expects(self::once())
            ->method('getMatchedPrices')
            ->with($productPriceCriteria, $priceScopeCriteria)
            ->willReturn($prices);

        self::assertSame(
            [$prices, $productPriceCriteria, $priceScopeCriteria],
            $this->provider->getMatchedPricesForLineItemsHolder($lineItemsHolder, self::USD)
        );
    }
}
