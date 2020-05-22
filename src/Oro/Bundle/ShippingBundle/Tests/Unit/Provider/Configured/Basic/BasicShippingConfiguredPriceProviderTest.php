<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Configured\Basic;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Method\Configuration\Composed\ComposedShippingMethodConfigurationInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Bundle\ShippingBundle\Provider\Price\Configured\Basic\BasicShippingConfiguredPriceProvider;
use Oro\Bundle\ShippingBundle\Provider\Price\ShippingPriceProviderInterface;

class BasicShippingConfiguredPriceProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var BasicShippingConfiguredPriceProvider
     */
    private $testedPriceProvider;

    /**
     * @var ShippingPriceProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $shippingPriceProviderMock;

    protected function setUp(): void
    {
        $this->shippingPriceProviderMock = $this->createMock(ShippingPriceProviderInterface::class);

        $this->testedPriceProvider = new BasicShippingConfiguredPriceProvider($this->shippingPriceProviderMock);
    }

    public function testGetApplicableMethodsViews()
    {
        $expectedMethods = (new ShippingMethodViewCollection())->addMethodView('flat_rate', []);
        $context = $this->getShippingContextMock();
        $configuration = $this->getConfigurationMock();

        $this->shippingPriceProviderMock
            ->expects($this->once())
            ->method('getApplicableMethodsViews')
            ->with($context)
            ->willReturn($expectedMethods);

        $actualMethods = $this->testedPriceProvider->getApplicableMethodsViews($configuration, $context);

        $this->assertEquals($expectedMethods, $actualMethods);
    }

    public function testGetPrice()
    {
        $shippingMethodId = 'flat_rage';
        $shippingMethodTypeId = 'primary';
        $expectedPrice = Price::create(12, 'USD');
        $context = $this->getShippingContextMock();
        $configuration = $this->getConfigurationMock();

        $this->shippingPriceProviderMock
            ->expects($this->once())
            ->method('getPrice')
            ->with($context, $shippingMethodId, $shippingMethodTypeId)
            ->willReturn($expectedPrice);

        $actualPrice = $this->testedPriceProvider->getPrice(
            $shippingMethodId,
            $shippingMethodTypeId,
            $configuration,
            $context
        );

        $this->assertEquals($expectedPrice, $actualPrice);
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
