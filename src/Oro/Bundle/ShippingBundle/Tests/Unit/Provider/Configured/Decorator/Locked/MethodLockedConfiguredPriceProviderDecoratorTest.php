<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Configured\Decorator\MethodLocked;

// @codingStandardsIgnoreStart
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Method\Configuration\Composed\ComposedShippingMethodConfigurationInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Bundle\ShippingBundle\Provider\Price\Configured\Decorator\Locked\MethodLockedConfiguredPriceProviderDecorator;
use Oro\Bundle\ShippingBundle\Provider\Price\Configured\ShippingConfiguredPriceProviderInterface;
// @codingStandardsIgnoreEnd

class MethodLockedConfiguredPriceProviderDecoratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ShippingConfiguredPriceProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $parentProviderMock;

    /**
     * @var MethodLockedConfiguredPriceProviderDecorator
     */
    private $testedProvider;

    public function setUp()
    {
        $this->parentProviderMock = $this
            ->getMockBuilder(ShippingConfiguredPriceProviderInterface::class)
            ->getMock();

        $this->testedProvider = new MethodLockedConfiguredPriceProviderDecorator($this->parentProviderMock);
    }

    public function testGetApplicableMethodsViews()
    {
        $configurationMock = $this->getConfigurationMock();
        $contextMock = $this->getShippingContextMock();

        $parentMethodViews = new ShippingMethodViewCollection();

        $parentMethodViews
            ->addMethodView('flat_rate', [])
            ->addMethodTypeView('flat_rate', 'primary', ['price' => Price::create(12, 'USD')])
            ->addMethodView('anotherMethod', [])
            ->addMethodTypeView('anotherMethod', 'anotherMethodType', ['price' => Price::create(12, 'USD')]);

        $this->parentProviderMock
            ->expects($this->once())
            ->method('getApplicableMethodsViews')
            ->with($configurationMock, $contextMock)
            ->willReturn($parentMethodViews);

        $configurationMock
            ->expects($this->once())
            ->method('isShippingMethodLocked')
            ->willReturn(true);

        $configurationMock
            ->expects($this->exactly(2))
            ->method('getShippingMethod')
            ->willReturn('flat_rate');

        $configurationMock
            ->expects($this->once())
            ->method('getShippingMethodType')
            ->willReturn('primary');

        $expectedMethods = clone $parentMethodViews;
        $expectedMethods
            ->removeMethodView('anotherMethod');

        $actualMethods = $this->testedProvider->getApplicableMethodsViews($configurationMock, $contextMock);

        $this->assertEquals($expectedMethods, $actualMethods);
    }

    public function testGetPrice()
    {
        $methodId = 'flat_rate';
        $methodTypeId = 'primary';
        $shippingCost = Price::create(50, 'EUR');
        $configurationMock = $this->getConfigurationMock();
        $contextMock = $this->getShippingContextMock();

        $this->parentProviderMock
            ->expects($this->once())
            ->method('getPrice')
            ->with($methodId, $methodTypeId, $configurationMock, $contextMock)
            ->willReturn($shippingCost);

        $actualPrice = $this->testedProvider->getPrice($methodId, $methodTypeId, $configurationMock, $contextMock);

        $this->assertEquals($shippingCost, $actualPrice);
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

    /**
     * @return ComposedShippingMethodConfigurationInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getConfigurationMock()
    {
        return $this
            ->getMockBuilder(ComposedShippingMethodConfigurationInterface::class)
            ->getMock();
    }
}
