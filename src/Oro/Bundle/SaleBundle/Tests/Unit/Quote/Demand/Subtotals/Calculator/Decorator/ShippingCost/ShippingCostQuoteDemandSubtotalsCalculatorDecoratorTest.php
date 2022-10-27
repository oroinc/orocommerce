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
    /**
     * @var ShippingContextFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $quoteShippingContextFactoryMock;

    /**
     * @var QuoteShippingConfigurationFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $quoteShippingConfigurationFactoryMock;

    /**
     * @var ShippingConfiguredPriceProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $shippingConfiguredPriceProviderMock;

    /**
     * @var QuoteDemandSubtotalsCalculatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $parentQuoteDemandSubtotalsCalculatorMock;

    /**
     * @var ShippingCostQuoteDemandSubtotalsCalculatorDecorator
     */
    private $shippingCostQuoteDemandSubtotalsCalculatorDecorator;

    protected function setUp(): void
    {
        $this->quoteShippingContextFactoryMock = $this
            ->getMockBuilder(ShippingContextFactoryInterface::class)
            ->getMock();

        $this->quoteShippingConfigurationFactoryMock = $this
            ->getMockBuilder(QuoteShippingConfigurationFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shippingConfiguredPriceProviderMock = $this
            ->getMockBuilder(ShippingConfiguredPriceProviderInterface::class)
            ->getMock();

        $this->parentQuoteDemandSubtotalsCalculatorMock = $this
            ->getMockBuilder(QuoteDemandSubtotalsCalculatorInterface::class)
            ->getMock();

        $this->shippingCostQuoteDemandSubtotalsCalculatorDecorator =
            new ShippingCostQuoteDemandSubtotalsCalculatorDecorator(
                $this->quoteShippingContextFactoryMock,
                $this->quoteShippingConfigurationFactoryMock,
                $this->shippingConfiguredPriceProviderMock,
                $this->parentQuoteDemandSubtotalsCalculatorMock
            );
    }

    /**
     * @dataProvider calculateSubtotalsProvider
     */
    public function testCalculateSubtotals($priceAmount, Price $price = null)
    {
        $shippingMethod = 'someShippingMethodId';
        $shippingMethodType = 'someShippingMethodTypeId';
        $expectedResult = ['someResult' => 'result'];

        $shippingContextMock = $this->getShippingContextMock();
        $configurationMock = $this->getComposedShippingMethodConfigurationMock();

        $quoteMock = $this->getQuoteMock();
        $quoteMock
            ->expects($this->once())
            ->method('getShippingMethod')
            ->willReturn($shippingMethod);
        $quoteMock
            ->expects($this->once())
            ->method('getShippingMethodType')
            ->willReturn($shippingMethodType);
        $quoteMock
            ->expects($this->once())
            ->method('setEstimatedShippingCostAmount')
            ->with($priceAmount);

        $quoteDemandMock = $this->getQuoteDemandMock();
        $quoteDemandMock
            ->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $this->quoteShippingContextFactoryMock
            ->expects($this->once())
            ->method('create')
            ->with($quoteMock)
            ->willReturn($shippingContextMock);

        $this->quoteShippingConfigurationFactoryMock
            ->expects($this->once())
            ->method('createQuoteShippingConfig')
            ->willReturn($configurationMock);

        $this->shippingConfiguredPriceProviderMock
            ->expects($this->once())
            ->method('getPrice')
            ->with($shippingMethod, $shippingMethodType, $configurationMock, $shippingContextMock)
            ->willReturn($price);

        $this->parentQuoteDemandSubtotalsCalculatorMock
            ->expects($this->once())
            ->method('calculateSubtotals')
            ->with($quoteDemandMock)
            ->willReturn($expectedResult);

        $actualResult = $this->shippingCostQuoteDemandSubtotalsCalculatorDecorator
            ->calculateSubtotals($quoteDemandMock);

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @return array
     */
    public function calculateSubtotalsProvider()
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

    /**
     * @return QuoteDemand|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getQuoteDemandMock()
    {
        return $this->getMockBuilder(QuoteDemand::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return Quote|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getQuoteMock()
    {
        return $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return ShippingContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getShippingContextMock()
    {
        return $this
            ->getMockBuilder(ShippingContextInterface::class)
            ->getMock();
    }

    /**
     * @return ComposedShippingMethodConfigurationInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getComposedShippingMethodConfigurationMock()
    {
        return $this
            ->getMockBuilder(ComposedShippingMethodConfigurationInterface::class)
            ->getMock();
    }
}
