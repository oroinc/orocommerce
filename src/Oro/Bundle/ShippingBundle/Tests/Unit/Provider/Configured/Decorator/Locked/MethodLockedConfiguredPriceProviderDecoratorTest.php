<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Configured\Decorator\Locked;

// @codingStandardsIgnoreStart
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Method\Configuration\Composed\ComposedShippingMethodConfigurationInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Bundle\ShippingBundle\Provider\Price\Configured\Decorator\Locked\MethodLockedConfiguredPriceProviderDecorator;
use Oro\Bundle\ShippingBundle\Provider\Price\Configured\ShippingConfiguredPriceProviderInterface;

// @codingStandardsIgnoreEnd

class MethodLockedConfiguredPriceProviderDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ShippingConfiguredPriceProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $parentProviderMock;

    /**
     * @var MethodLockedConfiguredPriceProviderDecorator
     */
    private $testedProvider;

    protected function setUp(): void
    {
        $this->parentProviderMock = $this
            ->createMock(ShippingConfiguredPriceProviderInterface::class);

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

    public function testGetApplicableMethodsViewsForNullConfigurationShippingMethod()
    {
        $configurationMock = $this->getConfigurationMock();
        $contextMock = $this->getShippingContextMock();

        $parentMethodViews = new ShippingMethodViewCollection();

        $parentMethodViews
            ->addMethodView('anotherMethod', [])
            ->addMethodTypeView('anotherMethod', 'anotherMethodType', ['price' => Price::create(12, 'USD')]);

        $this->parentProviderMock
            ->expects(static::once())
            ->method('getApplicableMethodsViews')
            ->with($configurationMock, $contextMock)
            ->willReturn($parentMethodViews);

        $configurationMock
            ->expects(static::once())
            ->method('getShippingMethod')
            ->willReturn(null);

        static::assertSame(
            $parentMethodViews,
            $this->testedProvider->getApplicableMethodsViews($configurationMock, $contextMock)
        );
    }

    public function testGetApplicableMethodsViewsForNotConfigurationShippingMethodLocked()
    {
        $configurationMock = $this->getConfigurationMock();
        $contextMock = $this->getShippingContextMock();

        $parentMethodViews = new ShippingMethodViewCollection();

        $parentMethodViews
            ->addMethodView('anotherMethod', [])
            ->addMethodTypeView('anotherMethod', 'anotherMethodType', ['price' => Price::create(12, 'USD')]);

        $this->parentProviderMock
            ->expects(static::once())
            ->method('getApplicableMethodsViews')
            ->with($configurationMock, $contextMock)
            ->willReturn($parentMethodViews);

        $configurationMock
            ->expects(static::once())
            ->method('getShippingMethod')
            ->willReturn('anotherMethod');

        $configurationMock
            ->expects(static::once())
            ->method('isShippingMethodLocked')
            ->willReturn(false);

        static::assertSame(
            $parentMethodViews,
            $this->testedProvider->getApplicableMethodsViews($configurationMock, $contextMock)
        );
    }

    public function testGetApplicableMethodsViewsIfMethodsViewsNotHasMethodTypeView()
    {
        $configurationMock = $this->getConfigurationMock();
        $contextMock = $this->getShippingContextMock();

        $parentMethodViews = new ShippingMethodViewCollection();

        $parentMethodViews
            ->addMethodView('anotherMethod', [])
            ->addMethodTypeView('anotherMethod', 'anotherMethodType', ['price' => Price::create(12, 'USD')]);

        $this->parentProviderMock
            ->expects(static::once())
            ->method('getApplicableMethodsViews')
            ->with($configurationMock, $contextMock)
            ->willReturn($parentMethodViews);

        $configurationMock
            ->expects(static::exactly(2))
            ->method('getShippingMethod')
            ->willReturn('anotherMethod2');

        $configurationMock
            ->expects(static::once())
            ->method('getShippingMethodType')
            ->willReturn('anotherMethodType2');

        $configurationMock
            ->expects(static::once())
            ->method('isShippingMethodLocked')
            ->willReturn(true);

        static::assertEquals(
            $parentMethodViews,
            $this->testedProvider->getApplicableMethodsViews($configurationMock, $contextMock)
        );
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
     * @return ShippingContext|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getShippingContextMock()
    {
        return $this->createMock(ShippingContext::class);
    }

    /**
     * @return ComposedShippingMethodConfigurationInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getConfigurationMock()
    {
        return $this->createMock(ComposedShippingMethodConfigurationInterface::class);
    }
}
