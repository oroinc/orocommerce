<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Quote\Demand\Subtotals\Calculator\Decorator\ShippingCost;

// @codingStandardsIgnoreStart
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Quote\Demand\Subtotals\Calculator\Decorator\ShippingCost\ShippingCostQuoteDemandSubtotalsCalculatorDecorator;
use Oro\Bundle\SaleBundle\Quote\Demand\Subtotals\Calculator\QuoteDemandSubtotalsCalculatorInterface;
use Oro\Bundle\SaleBundle\Quote\Shipping\Configuration\QuoteShippingConfigurationFactory;
use Oro\Bundle\ShippingBundle\Context\ShippingContextFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\Configuration\Composed\ComposedShippingMethodConfigurationInterface;
use Oro\Bundle\ShippingBundle\Provider\Price\Configured\ShippingConfiguredPriceProviderInterface;

// @codingStandardsIgnoreEnd

class ShippingCostQuoteDemandSubtotalsCalculatorDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShippingContextFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $quoteShippingContextFactory;

    /** @var QuoteShippingConfigurationFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $quoteShippingConfigurationFactory;

    /** @var ShippingConfiguredPriceProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingConfiguredPriceProvider;

    /** @var QuoteDemandSubtotalsCalculatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $parentQuoteDemandSubtotalsCalculator;

    /** @var ShippingCostQuoteDemandSubtotalsCalculatorDecorator */
    private $shippingCostQuoteDemandSubtotalsCalculatorDecorator;

    protected function setUp(): void
    {
        $this->quoteShippingContextFactory = $this->createMock(ShippingContextFactoryInterface::class);
        $this->quoteShippingConfigurationFactory = $this->createMock(QuoteShippingConfigurationFactory::class);
        $this->shippingConfiguredPriceProvider = $this->createMock(ShippingConfiguredPriceProviderInterface::class);
        $this->parentQuoteDemandSubtotalsCalculator = $this->createMock(QuoteDemandSubtotalsCalculatorInterface::class);

        $this->shippingCostQuoteDemandSubtotalsCalculatorDecorator =
            new ShippingCostQuoteDemandSubtotalsCalculatorDecorator(
                $this->quoteShippingContextFactory,
                $this->quoteShippingConfigurationFactory,
                $this->shippingConfiguredPriceProvider,
                $this->parentQuoteDemandSubtotalsCalculator
            );
    }

    /**
     * @dataProvider calculateSubtotalsProvider
     */
    public function testCalculateSubtotals(?int $priceAmount, Price $price = null)
    {
        $shippingMethod = 'someShippingMethodId';
        $shippingMethodType = 'someShippingMethodTypeId';
        $expectedResult = ['someResult' => 'result'];

        $shippingContext = $this->createMock(ShippingContextInterface::class);
        $configuration = $this->createMock(ComposedShippingMethodConfigurationInterface::class);

        $quote = $this->createMock(Quote::class);
        $quote->expects($this->once())
            ->method('getShippingMethod')
            ->willReturn($shippingMethod);
        $quote->expects($this->once())
            ->method('getShippingMethodType')
            ->willReturn($shippingMethodType);
        $quote->expects($this->once())
            ->method('setEstimatedShippingCostAmount')
            ->with($priceAmount);

        $quoteDemand = $this->createMock(QuoteDemand::class);
        $quoteDemand->expects($this->once())
            ->method('getQuote')
            ->willReturn($quote);

        $this->quoteShippingContextFactory->expects($this->once())
            ->method('create')
            ->with($quote)
            ->willReturn($shippingContext);

        $this->quoteShippingConfigurationFactory->expects($this->once())
            ->method('createQuoteShippingConfig')
            ->willReturn($configuration);

        $this->shippingConfiguredPriceProvider->expects($this->once())
            ->method('getPrice')
            ->with($shippingMethod, $shippingMethodType, $configuration, $shippingContext)
            ->willReturn($price);

        $this->parentQuoteDemandSubtotalsCalculator->expects($this->once())
            ->method('calculateSubtotals')
            ->with($quoteDemand)
            ->willReturn($expectedResult);

        $actualResult = $this->shippingCostQuoteDemandSubtotalsCalculatorDecorator
            ->calculateSubtotals($quoteDemand);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function calculateSubtotalsProvider(): array
    {
        return [
            'not null price test' => [
                12,
                Price::create(12, 'USD'),
            ],
            'null price test' => [
                null,
                null,
            ],
        ];
    }
}
