<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\CheckoutBundle\Layout\Extension\MultiShippingContextConfigurator;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Component\Layout\LayoutContext;

class MultiShippingContextConfiguratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var MultiShippingContextConfigurator */
    private $contextConfigurator;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->contextConfigurator = new MultiShippingContextConfigurator($this->configProvider);
    }

    public function testConfigureContextReturnTrue()
    {
        $this->configProvider->expects($this->once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(true);

        $context = new LayoutContext();
        $this->contextConfigurator->configureContext($context);

        $this->assertTrue($context->get('multi_shipping_enabled'));
    }

    public function testConfigureContextReturnFalse()
    {
        $this->configProvider->expects($this->once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(false);

        $context = new LayoutContext();
        $this->contextConfigurator->configureContext($context);

        $this->assertFalse($context->get('multi_shipping_enabled'));
    }
}
