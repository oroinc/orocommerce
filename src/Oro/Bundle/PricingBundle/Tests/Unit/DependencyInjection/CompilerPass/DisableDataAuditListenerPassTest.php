<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\PricingBundle\DependencyInjection\CompilerPass\DisableDataAuditListenerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class DisableDataAuditListenerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $containerBuilder;

    /** @var DisableDataAuditListenerPass */
    private $compilerPass;

    protected function setUp(): void
    {
        $this->containerBuilder = $this->createMock(ContainerBuilder::class);

        $this->compilerPass = new DisableDataAuditListenerPass();
    }

    public function testProcessNoParameter(): void
    {
        $this->containerBuilder->expects($this->once())
            ->method('hasParameter')
            ->with('installed')
            ->willReturn(false);

        $this->containerBuilder->expects($this->never())
            ->method('getDefinition');

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcessInstalled(): void
    {
        $this->containerBuilder->expects($this->once())
            ->method('hasParameter')
            ->with('installed')
            ->willReturn(true);

        $this->containerBuilder->expects($this->once())
            ->method('getParameter')
            ->with('installed')
            ->willReturn(true);

        $this->containerBuilder->expects($this->never())
            ->method('getDefinition');

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcessNoDefinition(): void
    {
        $this->containerBuilder->expects($this->once())
            ->method('hasParameter')
            ->with('installed')
            ->willReturn(true);

        $this->containerBuilder->expects($this->once())
            ->method('getParameter')
            ->with('installed')
            ->willReturn(false);

        $this->containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_pricing.entity_listener.send_changed_product_prices_to_message_queue')
            ->willReturn(false);

        $this->containerBuilder->expects($this->never())
            ->method('getDefinition');

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcessWithDefinition(): void
    {
        $this->containerBuilder->expects($this->once())
            ->method('hasParameter')
            ->with('installed')
            ->willReturn(true);

        $this->containerBuilder->expects($this->once())
            ->method('getParameter')
            ->with('installed')
            ->willReturn(false);

        $this->containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_pricing.entity_listener.send_changed_product_prices_to_message_queue')
            ->willReturn(true);

        $definition = $this->createMock(Definition::class);
        $definition->expects($this->once())
            ->method('addMethodCall')
            ->with('setEnabled', [false]);

        $this->containerBuilder->expects($this->once())
            ->method('getDefinition')
            ->with('oro_pricing.entity_listener.send_changed_product_prices_to_message_queue')
            ->willReturn($definition);

        $this->compilerPass->process($this->containerBuilder);
    }
}
