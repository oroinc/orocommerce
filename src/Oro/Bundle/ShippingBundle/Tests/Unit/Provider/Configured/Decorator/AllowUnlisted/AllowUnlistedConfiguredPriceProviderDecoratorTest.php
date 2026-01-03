<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Configured\Decorator\AllowUnlisted;

// phpcs:disable
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Method\Configuration\Composed\ComposedShippingMethodConfigurationInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewFactory;
use Oro\Bundle\ShippingBundle\Provider\Price\Configured\Decorator\AllowUnlisted\AllowUnlistedConfiguredPriceProviderDecorator;
use Oro\Bundle\ShippingBundle\Provider\Price\Configured\ShippingConfiguredPriceProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;

// phpcs:enable

class AllowUnlistedConfiguredPriceProviderDecoratorTest extends \PHPUnit\Framework\TestCase
{
    private ShippingConfiguredPriceProviderInterface|MockObject $parentProviderMock;

    private ShippingMethodViewFactory|MockObject $shippingMethodViewFactory;

    private AllowUnlistedConfiguredPriceProviderDecorator $testedProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->shippingMethodViewFactory = $this->createMock(ShippingMethodViewFactory::class);

        $this->parentProviderMock = $this
            ->createMock(ShippingConfiguredPriceProviderInterface::class);

        $this->testedProvider = new AllowUnlistedConfiguredPriceProviderDecorator(
            $this->shippingMethodViewFactory,
            $this->parentProviderMock
        );
    }

    public function testGetApplicableMethodsViews(): void
    {
        $methodId = 'flat_rate';
        $methodTypeId = 'primary';
        $flatRateShippingPrice = Price::create(50, 'USD');

        $configurationMock = $this->getConfigurationMock();
        $contextMock = $this->getShippingContextMock();

        $parentMethodViews = $this->getParentMethodViews();

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

    public function testGetApplicableMethodsViewsForNullConfigurationShippingMethod(): void
    {
        $configurationMock = $this->getConfigurationMock();
        $contextMock = $this->getShippingContextMock();
        $parentMethodViews = $this->getParentMethodViews();

        $this->parentProviderMock
            ->expects(static::once())
            ->method('getApplicableMethodsViews')
            ->with($configurationMock, $contextMock)
            ->willReturn($parentMethodViews);

        $configurationMock
            ->expects(static::once())
            ->method('getShippingMethod')
            ->willReturn(null);

        static::assertEquals(
            $parentMethodViews,
            $this->testedProvider->getApplicableMethodsViews($configurationMock, $contextMock)
        );
    }

    public function testGetApplicableMethodsViewsForNotAllowUnlistedShippingMethod(): void
    {
        $configurationMock = $this->getConfigurationMock();
        $contextMock = $this->getShippingContextMock();
        $parentMethodViews = $this->getParentMethodViews();

        $this->parentProviderMock
            ->expects(static::once())
            ->method('getApplicableMethodsViews')
            ->with($configurationMock, $contextMock)
            ->willReturn($parentMethodViews);

        $configurationMock
            ->expects(static::once())
            ->method('getShippingMethod')
            ->willReturn('method');

        $configurationMock
            ->expects(static::once())
            ->method('isAllowUnlistedShippingMethod')
            ->willReturn(false);

        static::assertEquals(
            $parentMethodViews,
            $this->testedProvider->getApplicableMethodsViews($configurationMock, $contextMock)
        );
    }

    public function testGetApplicableMethodsViewsIfTheyHasMethodTypeView(): void
    {
        $configurationMock = $this->getConfigurationMock();
        $contextMock = $this->getShippingContextMock();
        $parentMethodViews = $this->getParentMethodViews();

        $this->parentProviderMock
            ->expects(static::once())
            ->method('getApplicableMethodsViews')
            ->with($configurationMock, $contextMock)
            ->willReturn($parentMethodViews);

        $configurationMock
            ->expects(static::exactly(2))
            ->method('getShippingMethod')
            ->willReturn('anotherMethod');

        $configurationMock
            ->expects(static::once())
            ->method('getShippingMethodType')
            ->willReturn('anotherMethodType');

        $configurationMock
            ->expects(static::once())
            ->method('isAllowUnlistedShippingMethod')
            ->willReturn(true);

        static::assertEquals(
            $parentMethodViews,
            $this->testedProvider->getApplicableMethodsViews($configurationMock, $contextMock)
        );
    }

    public function testGetPrice(): void
    {
        $methodId = 'flat_rate';
        $methodTypeId = 'primary';
        $shippingCost = Price::create(50, 'EUR');

        $configurationMock = $this->getConfigurationMock();
        $contextMock = $this->getShippingContextMock();
        $parentMethodViews = $this->getParentMethodViews();

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

        $this->assertSame($shippingCost, $actualPrice);
    }

    public function testGetPriceIfNotAllowUnlistedShippingMethod(): void
    {
        $parentPrice = Price::create(50, 'EUR');
        $configurationMock = $this->getConfigurationMock();
        $contextMock = $this->getShippingContextMock();

        $configurationMock
            ->expects($this->once())
            ->method('isAllowUnlistedShippingMethod')
            ->willReturn(false);

        $this->parentProviderMock
            ->expects($this->once())
            ->method('getPrice')
            ->willReturn($parentPrice);

        static::assertSame(
            $parentPrice,
            $this->testedProvider->getPrice('method', 'type', $configurationMock, $contextMock)
        );
    }

    public function testGetPriceIfShippingMethodViewsHasMethodTypeView(): void
    {
        $parentPrice = Price::create(50, 'EUR');
        $configurationMock = $this->getConfigurationMock();
        $contextMock = $this->getShippingContextMock();
        $parentMethodViews = $this->getParentMethodViews();

        $configurationMock
            ->expects($this->once())
            ->method('isAllowUnlistedShippingMethod')
            ->willReturn(true);

        $this->parentProviderMock
            ->expects($this->once())
            ->method('getPrice')
            ->willReturn($parentPrice);

        $this->parentProviderMock
            ->expects($this->once())
            ->method('getApplicableMethodsViews')
            ->with($configurationMock, $contextMock)
            ->willReturn($parentMethodViews);

        static::assertSame(
            $parentPrice,
            $this->testedProvider->getPrice(
                'anotherMethod',
                'anotherMethodType',
                $configurationMock,
                $contextMock
            )
        );
    }

    public function testGetPriceIfShippingMethodTypeNotExist(): void
    {
        $shippingCost = Price::create(50, 'EUR');
        $configurationMock = $this->getConfigurationMock();
        $contextMock = $this->getShippingContextMock();

        $configurationMock
            ->expects($this->once())
            ->method('getShippingCost')
            ->willReturn($shippingCost);

        static::assertSame(
            $shippingCost,
            $this->testedProvider->getPrice('', '', $configurationMock, $contextMock)
        );
    }

    private function getShippingContextMock(): ShippingContext|MockObject
    {
        return $this->createMock(ShippingContext::class);
    }

    private function getConfigurationMock(): ComposedShippingMethodConfigurationInterface|MockObject
    {
        return $this->createMock(ComposedShippingMethodConfigurationInterface::class);
    }

    private function getParentMethodViews(): ShippingMethodViewCollection
    {
        $views = new ShippingMethodViewCollection();
        $views
            ->addMethodView('anotherMethod', [])
            ->addMethodTypeView('anotherMethod', 'anotherMethodType', ['price' => Price::create(12, 'USD')]);

        return $views;
    }
}
