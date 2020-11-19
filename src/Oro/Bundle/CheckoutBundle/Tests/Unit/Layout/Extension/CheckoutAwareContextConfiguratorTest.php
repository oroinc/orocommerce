<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\CheckoutBundle\Layout\Extension\CheckoutAwareContextConfigurator;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PromotionBundle\Tests\Unit\Entity\Stub\Checkout;
use Oro\Component\Layout\LayoutContext;

class CheckoutAwareContextConfiguratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager */
    private $configManager;

    /** @var CheckoutAwareContextConfigurator */
    private $configurator;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->configurator = new CheckoutAwareContextConfigurator($this->configManager);
    }

    public function testConfigureContextNoRequiredKeys(): void
    {
        $this->configManager->expects($this->never())
            ->method('get');

        $context = new LayoutContext();
        $context->data()->set('test', new Checkout());

        $this->configurator->configureContext($context);

        $this->assertFalse($context->has('newCheckoutPageLayout'));
    }

    public function testConfigureContextCheckoutIsNotInstanceof(): void
    {
        $this->configManager->expects($this->never())
            ->method('get');

        $context = new LayoutContext();
        $context->data()->set('checkout', new \stdClass());

        $this->configurator->configureContext($context);

        $this->assertFalse($context->has('newCheckoutPageLayout'));
    }

    public function testConfigureContextEntityIsNotInstanceof(): void
    {
        $this->configManager->expects($this->never())
            ->method('get');

        $context = new LayoutContext();
        $context->data()->set('entity', new \stdClass());

        $this->configurator->configureContext($context);

        $this->assertFalse($context->has('newCheckoutPageLayout'));
    }

    /**
     * @dataProvider configManagerDataProvider
     *
     * @param bool $value
     */
    public function testConfigureContextCheckoutIsInstanceof(bool $value): void
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_checkout.use_new_layout_for_checkout_page')
            ->willReturn($value);

        $context = new LayoutContext();
        $context->data()->set('checkout', new Checkout());

        $this->configurator->configureContext($context);

        $this->assertTrue($context->has('newCheckoutPageLayout'));
        $this->assertSame($value, $context->get('newCheckoutPageLayout'));
    }

    /**
     * @dataProvider configManagerDataProvider
     *
     * @param bool $value
     */
    public function testConfigureContextEntityIsInstanceof(bool $value): void
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_checkout.use_new_layout_for_checkout_page')
            ->willReturn($value);

        $context = new LayoutContext();
        $context->data()->set('entity', new Checkout());

        $this->configurator->configureContext($context);

        $this->assertTrue($context->has('newCheckoutPageLayout'));
        $this->assertSame($value, $context->get('newCheckoutPageLayout'));
    }

    /**
     * @return array
     */
    public function configManagerDataProvider(): array
    {
        return [
            [false],
            [true]
        ];
    }
}
