<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\PricingBundle\DependencyInjection\CompilerPass\DisableDataAuditListenerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DisableDataAuditListenerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var DisableDataAuditListenerPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new DisableDataAuditListenerPass();
    }

    public function testProcessInstalled(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('installed', true);
        $listenerDef = $container->register('oro_pricing.entity_listener.send_changed_product_prices_to_message_queue');

        $this->compiler->process($container);

        self::assertSame([], $listenerDef->getMethodCalls());
    }

    public function testProcessNotInstalledButNoListener(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('installed', null);

        $this->compiler->process($container);
    }

    public function testProcessNoInstalledParameter(): void
    {
        $container = new ContainerBuilder();
        $listenerDef = $container->register('oro_pricing.entity_listener.send_changed_product_prices_to_message_queue');

        $this->compiler->process($container);

        self::assertEquals(
            [
                ['setEnabled', [false]]
            ],
            $listenerDef->getMethodCalls()
        );
    }

    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('installed', null);
        $listenerDef = $container->register('oro_pricing.entity_listener.send_changed_product_prices_to_message_queue');

        $this->compiler->process($container);

        self::assertEquals(
            [
                ['setEnabled', [false]]
            ],
            $listenerDef->getMethodCalls()
        );
    }
}
