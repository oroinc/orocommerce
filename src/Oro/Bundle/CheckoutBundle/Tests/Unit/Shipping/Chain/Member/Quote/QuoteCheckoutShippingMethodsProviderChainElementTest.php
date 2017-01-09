<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Shipping\Chain\Member\Quote;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\ShippingContextProviderFactory;
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

class QuoteCheckoutShippingMethodsProviderChainElementTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var QuoteCheckoutShippingMethodsProviderChainElement
     */
    private $testedMethodsProvider;

    /**
     * @var ShippingContextProviderFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutShippingContextFactoryMock;

    /**
     * @var ShippingConfiguredPriceProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingConfiguredPriceProviderMock;

    /**
     * @var QuoteShippingConfigurationFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteShippingConfigurationFactoryMock;

    public function setUp()
    {
        $this->checkoutShippingContextFactoryMock = $this->getMockBuilder(ShippingContextProviderFactory::class)
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
            $this->checkoutShippingContextFactoryMock,
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

        $this->checkoutShippingContextFactoryMock
            ->expects($this->once())
            ->method('create')
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

        $this->checkoutShippingContextFactoryMock
            ->expects($this->never())
            ->method('create');

        $this->quoteShippingConfigurationFactoryMock
            ->expects($this->never())
            ->method('createQuoteShippingConfig');

        $this->shippingConfiguredPriceProviderMock
            ->expects($this->never())
            ->method('getApplicableMethodsViews');

        $actualMethods = $this->testedMethodsProvider->getApplicableMethodsViews($checkoutMock);

        $this->assertEquals($expectedMethods, $actualMethods);
    }

    public function testGetApplicableMethodsViewsWithSuccessor()
    {
        $quoteDemandMock = $this->getQuoteDemandMock();
        $checkoutMock = $this->getCheckoutMock();
        $expectedMethods = (new ShippingMethodViewCollection())
            ->addMethodView('flat_rate', ['identifier' => 'flat_rate']);

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

        $this->checkoutShippingContextFactoryMock
            ->expects($this->never())
            ->method('create');

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

        $this->checkoutShippingContextFactoryMock
            ->expects($this->once())
            ->method('create')
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

        $this->checkoutShippingContextFactoryMock
            ->expects($this->never())
            ->method('create');

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
     * @return CheckoutShippingMethodsProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getSuccessorMock()
    {
        return $this
            ->getMockBuilder(CheckoutShippingMethodsProviderInterface::class)
            ->getMock();
    }

    /**
     * @return ComposedShippingMethodConfigurationInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getConfigurationMock()
    {
        return $this
            ->getMockBuilder(ComposedShippingMethodConfigurationInterface::class)
            ->getMock();
    }

    /**
     * @return QuoteDemand|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getQuoteDemandMock()
    {
        return $this
            ->getMockBuilder(QuoteDemand::class)
            ->getMock();
    }

    /**
     * @return Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getQuoteMock()
    {
        return $this
            ->getMockBuilder(Quote::class)
            ->getMock();
    }

    /**
     * @return Checkout|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getCheckoutMock()
    {
        return $this
            ->getMockBuilder(Checkout::class)
            ->getMock();
    }

    /**
     * @return ShippingContext|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getShippingContextMock()
    {
        return $this
            ->getMockBuilder(ShippingContext::class)
            ->getMock();
    }
}
