<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Configured\Decorator\OverriddenCost;

// @codingStandardsIgnoreStart
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Method\Configuration\Composed\ComposedShippingMethodConfigurationInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Bundle\ShippingBundle\Provider\Price\Configured\Decorator\OverriddenCost\OverriddenCostShippingConfiguredPriceProviderDecorator;
use Oro\Bundle\ShippingBundle\Provider\Price\Configured\ShippingConfiguredPriceProviderInterface;

// @codingStandardsIgnoreEnd

class OverriddenCostShippingConfiguredPriceProviderDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ShippingConfiguredPriceProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $parentProviderMock;

    /**
     * @var OverriddenCostShippingConfiguredPriceProviderDecorator
     */
    private $testedProvider;

    protected function setUp(): void
    {
        $this->parentProviderMock = $this
            ->createMock(ShippingConfiguredPriceProviderInterface::class);

        $this->testedProvider = new OverriddenCostShippingConfiguredPriceProviderDecorator($this->parentProviderMock);
    }

    public function testGetApplicableMethodsViews()
    {
        $overriddenShippingCost = Price::create(50, 'EUR');
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
            ->method('isOverriddenShippingCost')
            ->willReturn(true);

        $configurationMock
            ->expects($this->once())
            ->method('getShippingCost')
            ->willReturn($overriddenShippingCost);

        $expectedMethods = clone $parentMethodViews;
        $expectedMethods
            ->removeMethodTypeView('flat_rate', 'primary')
            ->addMethodTypeView('flat_rate', 'primary', ['price' => $overriddenShippingCost]);

        $actualMethods = $this->testedProvider->getApplicableMethodsViews($configurationMock, $contextMock);

        $this->assertEquals($expectedMethods, $actualMethods);
    }

    public function testGetApplicableMethodsViewsForNotConfigurationOverriddenShippingCost()
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
            ->method('isOverriddenShippingCost')
            ->willReturn(false);

        static::assertSame(
            $parentMethodViews,
            $this->testedProvider->getApplicableMethodsViews($configurationMock, $contextMock)
        );
    }

    public function testGetPrice()
    {
        $methodId = 'flat_rate';
        $methodTypeId = 'primary';
        $overriddenShippingCost = Price::create(50, 'EUR');
        $configurationMock = $this->getConfigurationMock();
        $contextMock = $this->getShippingContextMock();

        $configurationMock
            ->expects($this->once())
            ->method('isOverriddenShippingCost')
            ->willReturn(true);

        $configurationMock
            ->expects($this->once())
            ->method('getShippingCost')
            ->willReturn($overriddenShippingCost);

        $actualPrice = $this->testedProvider->getPrice($methodId, $methodTypeId, $configurationMock, $contextMock);

        $this->assertEquals($overriddenShippingCost, $actualPrice);
    }

    public function testGetPriceForNotConfigurationOverriddenShippingCost()
    {
        $methodId = 'flat_rate';
        $methodTypeId = 'primary';
        $shippingCost = Price::create(50, 'EUR');
        $configurationMock = $this->getConfigurationMock();
        $contextMock = $this->getShippingContextMock();

        $configurationMock
            ->expects(static::once())
            ->method('isOverriddenShippingCost')
            ->willReturn(false);

        $this->parentProviderMock
            ->expects(static::once())
            ->method('getPrice')
            ->with($methodId, $methodTypeId, $configurationMock, $contextMock)
            ->willReturn($shippingCost);

        static::assertSame(
            $shippingCost,
            $this->testedProvider->getPrice(
                $methodId,
                $methodTypeId,
                $configurationMock,
                $contextMock
            )
        );
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

    /**
     * @return ShippingMethodViewCollection
     */
    private function getParentMethodViews()
    {
        $parentMethodViews = new ShippingMethodViewCollection();

        $parentMethodViews
            ->addMethodView('flat_rate', [])
            ->addMethodTypeView('flat_rate', 'primary', ['price' => Price::create(12, 'USD')]);

        return $parentMethodViews;
    }
}
