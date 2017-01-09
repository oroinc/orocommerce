<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Configured\Decorator\AllowUnlisted;

// @codingStandardsIgnoreStart
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Method\Configuration\Composed\ComposedShippingMethodConfigurationInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewFactory;
use Oro\Bundle\ShippingBundle\Provider\Price\Configured\Decorator\AllowUnlisted\AllowUnlistedConfiguredPriceProviderDecorator;
use Oro\Bundle\ShippingBundle\Provider\Price\Configured\ShippingConfiguredPriceProviderInterface;
// @codingStandardsIgnoreEnd

class AllowUnlistedConfiguredPriceProviderDecoratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ShippingConfiguredPriceProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $parentProviderMock;

    /**
     * @var ShippingMethodViewFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingMethodViewFactory;

    /**
     * @var AllowUnlistedConfiguredPriceProviderDecorator
     */
    private $testedProvider;

    public function setUp()
    {
        $this->shippingMethodViewFactory = $this->getMockBuilder(ShippingMethodViewFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->parentProviderMock = $this
            ->getMockBuilder(ShippingConfiguredPriceProviderInterface::class)
            ->getMock();

        $this->testedProvider = new AllowUnlistedConfiguredPriceProviderDecorator(
            $this->shippingMethodViewFactory,
            $this->parentProviderMock
        );
    }

    public function testGetApplicableMethodsViews()
    {
        $methodId = 'flat_rate';
        $methodTypeId = 'primary';
        $flatRateShippingPrice = Price::create(50, 'USD');

        $configurationMock = $this->getConfigurationMock();
        $contextMock = $this->getShippingContextMock();

        $parentMethodViews = new ShippingMethodViewCollection();

        $parentMethodViews
            ->addMethodView('anotherMethod', [])
            ->addMethodTypeView('anotherMethod', 'anotherMethodType', ['price' => Price::create(12, 'USD')]);

        $this->parentProviderMock
            ->expects($this->once())
            ->method('getApplicableMethodsViews')
            ->with($configurationMock, $contextMock)
            ->willReturn($parentMethodViews);

        $configurationMock
            ->expects($this->once())
            ->method('isAllowUnlistedShippingMethod')
            ->willReturn(true);

        $configurationMock
            ->expects($this->exactly(2))
            ->method('getShippingMethod')
            ->willReturn('flat_rate');

        $configurationMock
            ->expects($this->once())
            ->method('getShippingMethodType')
            ->willReturn('primary');

        $configurationMock
            ->expects($this->once())
            ->method('getShippingCost')
            ->willReturn($flatRateShippingPrice);

        $flatRateView = [
            'identifier' => 'flat_rate',
            'isGrouped' => true,
            'label' => 'FlatRate',
            'sortOrder' => 1,
        ];

        $flatRateTypeView = [
            'identifier' => 'primary',
            'label' => 'Primary',
            'sortOrder' => 2,
            'price' => $flatRateShippingPrice,
        ];

        $this->shippingMethodViewFactory
            ->expects($this->once())
            ->method('createMethodViewByShippingMethod')
            ->with($methodId)
            ->willReturn($flatRateView);

        $this->shippingMethodViewFactory
            ->expects($this->once())
            ->method('createMethodTypeViewByShippingMethodAndPrice')
            ->with($methodId, $methodTypeId, $flatRateShippingPrice)
            ->willReturn($flatRateTypeView);

        $expectedMethods = clone $parentMethodViews;
        $expectedMethods
            ->addMethodView('flat_rate', $flatRateView)
            ->addMethodTypeView('flat_rate', 'primary', $flatRateTypeView);

        $actualMethods = $this->testedProvider->getApplicableMethodsViews($configurationMock, $contextMock);

        $this->assertEquals($expectedMethods, $actualMethods);
    }

    public function getPrice()
    {
        $methodId = 'flat_rate';
        $methodTypeId = 'primary';
        $shippingCost = Price::create(50, 'EUR');
        $configurationMock = $this->getConfigurationMock();
        $contextMock = $this->getShippingContextMock();

        $parentMethodViews = new ShippingMethodViewCollection();
        $parentMethodViews
            ->addMethodView('anotherMethod', [])
            ->addMethodTypeView('anotherMethod', 'anotherMethodType', ['price' => Price::create(12, 'USD')]);

        $this->parentProviderMock
            ->expects($this->once())
            ->method('getApplicableMethodsViews')
            ->with($configurationMock, $contextMock)
            ->willReturn($parentMethodViews);

        $configurationMock
            ->expects($this->once())
            ->method('isAllowUnlistedShippingMethod')
            ->willReturn(true);

        $configurationMock
            ->expects($this->once())
            ->method('getShippingCost')
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
