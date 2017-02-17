<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Shipping\Chain\Member\Price;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutShippingContextFactory;
use Oro\Bundle\CheckoutBundle\Shipping\Method\Chain\Member\Price\PriceCheckoutShippingMethodsProviderChainElement;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;

class PriceCheckoutShippingMethodsProviderChainElementTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PriceCheckoutShippingMethodsProviderChainElement
     */
    private $testedMethodsProvider;

    /**
     * @var ShippingPriceProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingPriceProviderMock;

    /**
     * @var CheckoutShippingContextFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingContextFactoryMock;

    public function setUp()
    {
        $this->shippingPriceProviderMock = $this
            ->getMockBuilder(ShippingPriceProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shippingContextFactoryMock = $this
            ->getMockBuilder(CheckoutShippingContextFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->testedMethodsProvider = new PriceCheckoutShippingMethodsProviderChainElement(
            $this->shippingPriceProviderMock,
            $this->shippingContextFactoryMock
        );
    }

    public function testGetApplicableMethodsViewsWithoutSuccessor()
    {
        $checkoutMock = $this->getCheckoutMock();
        $shippingContextMock = $this->getShippingContextMock();
        $expectedMethods = (new ShippingMethodViewCollection())
            ->addMethodView('flat_rate', ['identifier' => 'flat_rate']);

        $this->shippingContextFactoryMock
            ->expects($this->once())
            ->method('create')
            ->with($checkoutMock)
            ->willReturn($shippingContextMock);

        $this->shippingPriceProviderMock
            ->expects($this->once())
            ->method('getApplicableMethodsViews')
            ->with($shippingContextMock)
            ->willReturn($expectedMethods);

        $actualMethods = $this->testedMethodsProvider->getApplicableMethodsViews($checkoutMock);

        $this->assertEquals($expectedMethods, $actualMethods);
    }

    public function testGetApplicableMethodsViewsWithSuccessor()
    {
        $checkoutMock = $this->getCheckoutMock();
        $expectedMethods = (new ShippingMethodViewCollection())
            ->addMethodView('flat_rate', ['identifier' => 'flat_rate']);

        $successorMock = $this->getSuccessorMock();
        $this->testedMethodsProvider->setSuccessor($successorMock);

        $this->shippingContextFactoryMock
            ->expects($this->never())
            ->method('create');

        $this->shippingPriceProviderMock
            ->expects($this->never())
            ->method('getApplicableMethodsViews');

        $successorMock
            ->expects($this->once())
            ->method('getApplicableMethodsViews')
            ->with($checkoutMock)
            ->willReturn($expectedMethods);

        $actualMethods = $this->testedMethodsProvider->getApplicableMethodsViews($checkoutMock);

        $this->assertEquals($expectedMethods, $actualMethods);
    }

    public function testGetPriceWithoutSuccessor()
    {
        $shippingMethod = 'flat_rate';
        $shippingMethodType = 'primary';
        $price = Price::create(12, 'USD');
        $checkoutMock = $this->getCheckoutMock();
        $shippingContextMock = $this->getShippingContextMock();

        $checkoutMock
            ->expects($this->once())
            ->method('getShippingMethod')
            ->willReturn($shippingMethod);

        $checkoutMock
            ->expects($this->once())
            ->method('getShippingMethodType')
            ->willReturn($shippingMethodType);

        $this->shippingContextFactoryMock
            ->expects($this->once())
            ->method('create')
            ->with($checkoutMock)
            ->willReturn($shippingContextMock);

        $this->shippingPriceProviderMock
            ->expects($this->once())
            ->method('getPrice')
            ->with($shippingContextMock, $shippingMethod, $shippingMethodType)
            ->willReturn($price);

        $actualPrice = $this->testedMethodsProvider->getPrice($checkoutMock);

        $this->assertEquals($price, $actualPrice);
    }

    public function testGetPriceWithSuccessor()
    {
        $price = Price::create(12, 'USD');
        $checkoutMock = $this->getCheckoutMock();

        $successorMock = $this->getSuccessorMock();
        $this->testedMethodsProvider->setSuccessor($successorMock);

        $checkoutMock
            ->expects($this->never())
            ->method('getShippingMethod');

        $checkoutMock
            ->expects($this->never())
            ->method('getShippingMethodType');

        $this->shippingContextFactoryMock
            ->expects($this->never())
            ->method('create');

        $this->shippingPriceProviderMock
            ->expects($this->never())
            ->method('getPrice');

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
            ->disableOriginalConstructor()
            ->getMock();
    }
}
