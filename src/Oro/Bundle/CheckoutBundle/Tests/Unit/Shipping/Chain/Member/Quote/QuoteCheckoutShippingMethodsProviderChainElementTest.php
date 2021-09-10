<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Shipping\Chain\Member\Quote;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutShippingContextProvider;
use Oro\Bundle\CheckoutBundle\Shipping\Method\Chain\Member\Quote\QuoteCheckoutShippingMethodsProviderChainElement;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Quote\Shipping\Configuration\QuoteShippingConfigurationFactory;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Method\Configuration\Composed\ComposedShippingMethodConfigurationInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Bundle\ShippingBundle\Provider\Price\Configured\ShippingConfiguredPriceProviderInterface;

class QuoteCheckoutShippingMethodsProviderChainElementTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var QuoteCheckoutShippingMethodsProviderChainElement
     */
    private $testedMethodsProvider;

    /**
     * @var CheckoutShippingContextProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $checkoutShippingContextProvider;

    /**
     * @var ShippingConfiguredPriceProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $shippingConfiguredPriceProviderMock;

    /**
     * @var QuoteShippingConfigurationFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $quoteShippingConfigurationFactoryMock;

    protected function setUp(): void
    {
        $this->checkoutShippingContextProvider = $this->getMockBuilder(CheckoutShippingContextProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shippingConfiguredPriceProviderMock = $this
            ->getMockBuilder(ShippingConfiguredPriceProviderInterface::class)
            ->getMock();

        $this->quoteShippingConfigurationFactoryMock = $this
            ->getMockBuilder(QuoteShippingConfigurationFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->testedMethodsProvider = new QuoteCheckoutShippingMethodsProviderChainElement(
            $this->checkoutShippingContextProvider,
            $this->shippingConfiguredPriceProviderMock,
            $this->quoteShippingConfigurationFactoryMock
        );
    }

    public function testGetApplicableMethodsViews()
    {
        $quoteMock = $this->getQuoteMock();
        $quoteDemandMock = $this->getQuoteDemandMock();
        $checkoutMock = $this->getCheckoutMock();
        $shippingContextMock = $this->getShippingContextMock();
        $configurationMock = $this->getConfigurationMock();
        $expectedMethods = (new ShippingMethodViewCollection())
            ->addMethodView('flat_rate', ['identifier' => 'flat_rate']);

        $quoteDemandMock
            ->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $checkoutMock
            ->expects($this->once())
            ->method('getSourceEntity')
            ->willReturn($quoteDemandMock);

        $this->checkoutShippingContextProvider
            ->expects($this->once())
            ->method('getContext')
            ->with($checkoutMock)
            ->willReturn($shippingContextMock);

        $this->quoteShippingConfigurationFactoryMock
            ->expects($this->once())
            ->method('createQuoteShippingConfig')
            ->with($quoteMock)
            ->willReturn($configurationMock);

        $this->shippingConfiguredPriceProviderMock
            ->expects($this->once())
            ->method('getApplicableMethodsViews')
            ->with($configurationMock, $shippingContextMock)
            ->willReturn($expectedMethods);

        $actualMethods = $this->testedMethodsProvider->getApplicableMethodsViews($checkoutMock);

        $this->assertEquals($expectedMethods, $actualMethods);
    }

    public function testGetApplicableMethodsViewsWhenSourceEntityNotQuote()
    {
        $quoteDemandMock = $this->getQuoteDemandMock();
        $checkoutMock = $this->getCheckoutMock();
        $expectedMethods = (new ShippingMethodViewCollection());

        $checkoutMock
            ->expects($this->once())
            ->method('getSourceEntity')
            ->willReturn(new \stdClass());

        $quoteDemandMock
            ->expects($this->never())
            ->method('getQuote');

        $this->checkoutShippingContextProvider
            ->expects($this->never())
            ->method('getContext');

        $this->quoteShippingConfigurationFactoryMock
            ->expects($this->never())
            ->method('createQuoteShippingConfig');

        $this->shippingConfiguredPriceProviderMock
            ->expects($this->never())
            ->method('getApplicableMethodsViews');

        $actualMethods = $this->testedMethodsProvider->getApplicableMethodsViews($checkoutMock);

        $this->assertEquals($expectedMethods, $actualMethods);
    }

    public function testGetApplicableMethodsViewsWithSuccessorNotEmpty()
    {
        $quoteDemandMock = $this->getQuoteDemandMock();
        $checkoutMock = $this->getCheckoutMock();
        $expectedMethods = (new ShippingMethodViewCollection())
            ->addMethodView('flat_rate', ['identifier' => 'flat_rate'])
            ->addMethodTypeView('flat_rate', 'flat_rate_1', ['identifier' => 'flat_rate_1']);

        $successorMock = $this->getSuccessorMock();
        $this->testedMethodsProvider->setSuccessor($successorMock);

        $successorMock
            ->expects($this->once())
            ->method('getApplicableMethodsViews')
            ->with($checkoutMock)
            ->willReturn($expectedMethods);

        $checkoutMock
            ->expects($this->never())
            ->method('getSourceEntity');

        $quoteDemandMock
            ->expects($this->never())
            ->method('getQuote');

        $this->checkoutShippingContextProvider
            ->expects($this->never())
            ->method('getContext');

        $this->quoteShippingConfigurationFactoryMock
            ->expects($this->never())
            ->method('createQuoteShippingConfig');

        $this->shippingConfiguredPriceProviderMock
            ->expects($this->never())
            ->method('getApplicableMethodsViews');

        $actualMethods = $this->testedMethodsProvider->getApplicableMethodsViews($checkoutMock);

        $this->assertEquals($expectedMethods, $actualMethods);
    }

    public function testGetPrice()
    {
        $price = Price::create(12, 'USD');
        $shippingMethodId = 'shippingMethodId';
        $shippingMethodTypeId = 'shippingMethodTypeId';
        $quoteMock = $this->getQuoteMock();
        $quoteDemandMock = $this->getQuoteDemandMock();
        $checkoutMock = $this->getCheckoutMock();
        $shippingContextMock = $this->getShippingContextMock();
        $configurationMock = $this->getConfigurationMock();

        $checkoutMock
            ->expects($this->once())
            ->method('getShippingMethod')
            ->willReturn($shippingMethodId);

        $checkoutMock
            ->expects($this->once())
            ->method('getShippingMethodType')
            ->willReturn($shippingMethodTypeId);

        $checkoutMock
            ->expects($this->once())
            ->method('getSourceEntity')
            ->willReturn($quoteDemandMock);

        $quoteDemandMock
            ->expects($this->once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $this->checkoutShippingContextProvider
            ->expects($this->once())
            ->method('getContext')
            ->with($checkoutMock)
            ->willReturn($shippingContextMock);

        $this->quoteShippingConfigurationFactoryMock
            ->expects($this->once())
            ->method('createQuoteShippingConfig')
            ->with($quoteMock)
            ->willReturn($configurationMock);

        $this->shippingConfiguredPriceProviderMock
            ->expects($this->once())
            ->method('getPrice')
            ->with($shippingMethodId, $shippingMethodTypeId, $configurationMock, $shippingContextMock)
            ->willReturn($price);

        $actualPrice = $this->testedMethodsProvider->getPrice($checkoutMock);

        $this->assertEquals($price, $actualPrice);
    }

    public function testGetPriceWhenSourceEntityNotQuote()
    {
        $price = null;
        $quoteDemandMock = $this->getQuoteDemandMock();
        $checkoutMock = $this->getCheckoutMock();

        $checkoutMock
            ->expects($this->never())
            ->method('getShippingMethod');

        $checkoutMock
            ->expects($this->never())
            ->method('getShippingMethodType');

        $checkoutMock
            ->expects($this->once())
            ->method('getSourceEntity')
            ->willReturn(new \stdClass());

        $quoteDemandMock
            ->expects($this->never())
            ->method('getQuote');

        $this->checkoutShippingContextProvider
            ->expects($this->never())
            ->method('getContext');

        $this->quoteShippingConfigurationFactoryMock
            ->expects($this->never())
            ->method('createQuoteShippingConfig');

        $this->shippingConfiguredPriceProviderMock
            ->expects($this->never())
            ->method('getPrice');

        $actualPrice = $this->testedMethodsProvider->getPrice($checkoutMock);

        $this->assertEquals($price, $actualPrice);
    }

    public function testGetPriceWithSuccessor()
    {
        $price = Price::create(12, 'USD');
        $checkoutMock = $this->getCheckoutMock();

        $successorMock = $this->getSuccessorMock();
        $this->testedMethodsProvider->setSuccessor($successorMock);

        $successorMock
            ->expects($this->once())
            ->method('getPrice')
            ->with($checkoutMock)
            ->willReturn($price);

        $actualPrice = $this->testedMethodsProvider->getPrice($checkoutMock);

        $this->assertEquals($price, $actualPrice);
    }

    /**
     * @return CheckoutShippingMethodsProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getSuccessorMock()
    {
        return $this
            ->getMockBuilder(CheckoutShippingMethodsProviderInterface::class)
            ->getMock();
    }

    /**
     * @return ComposedShippingMethodConfigurationInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getConfigurationMock()
    {
        return $this
            ->getMockBuilder(ComposedShippingMethodConfigurationInterface::class)
            ->getMock();
    }

    /**
     * @return QuoteDemand|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getQuoteDemandMock()
    {
        return $this
            ->getMockBuilder(QuoteDemand::class)
            ->getMock();
    }

    /**
     * @return Quote|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getQuoteMock()
    {
        return $this
            ->getMockBuilder(Quote::class)
            ->getMock();
    }

    /**
     * @return Checkout|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getCheckoutMock()
    {
        return $this
            ->getMockBuilder(Checkout::class)
            ->getMock();
    }

    /**
     * @return ShippingContext|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getShippingContextMock()
    {
        return $this
            ->getMockBuilder(ShippingContext::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
