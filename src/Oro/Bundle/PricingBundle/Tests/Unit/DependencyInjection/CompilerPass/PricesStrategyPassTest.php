<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\PricingBundle\DependencyInjection\CompilerPass\PricesStrategyPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class PricesStrategyPassTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ContainerBuilder
     */
    protected $containerBuilder;

    /**
     * @var PricesStrategyPass
     */
    protected $compilerPass;

    protected function setUp(): void
    {
        $this->containerBuilder = $this->createMock(ContainerBuilder::class);
        $this->compilerPass = new PricesStrategyPass();
    }

    protected function tearDown(): void
    {
        unset($this->containerBuilder, $this->compilerPass);
    }

    public function testProcessNotStrategyRegister()
    {
        $this->containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with(PricesStrategyPass::STRATEGY_REGISTER)
            ->willReturn(false);
        $this->containerBuilder->expects($this->never())
            ->method('getDefinition');
        $this->containerBuilder->expects($this->never())
            ->method('findTaggedServiceIds');
        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcess()
    {
        $definition = $this->createMock(Definition::class);
        $definition->expects($this->at(0))
            ->method('addMethodCall')
            ->with('add', ['first', new Reference('strategy_register1')]);
        $definition->expects($this->at(1))
            ->method('addMethodCall')
            ->with('add', ['second', new Reference('strategy_register2')]);
        $this->containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with(PricesStrategyPass::STRATEGY_REGISTER)
            ->willReturn(true);
        $this->containerBuilder->expects($this->once())
            ->method('getDefinition')
            ->with(PricesStrategyPass::STRATEGY_REGISTER)
            ->willReturn($definition);
        $this->containerBuilder->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(PricesStrategyPass::STRATEGY_TAG)
            ->willReturn(
                [
                    'strategy_register1' => [[PricesStrategyPass::STRATEGY_ALIAS => 'first']],
                    'strategy_register2' => [[PricesStrategyPass::STRATEGY_ALIAS => 'second']],
                ]
            );
        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcessNoTagged()
    {
        $definition = $this->createMock(Definition::class);
        $definition->expects($this->never())
            ->method('addMethodCall');
        $this->containerBuilder->expects($this->once())
            ->method('hasDefinition')
            ->with(PricesStrategyPass::STRATEGY_REGISTER)
            ->willReturn(true);
        $this->containerBuilder->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(PricesStrategyPass::STRATEGY_TAG)
            ->willReturn([]);
        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcessTypeIsMissing()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Attribute "alias" is missing for "oro_pricing.price_strategy" tag at "service.name.1" service'
        );

        $this->containerBuilder
            ->expects($this->once())
            ->method('hasDefinition')
            ->with(PricesStrategyPass::STRATEGY_REGISTER)
            ->willReturn(true);
        $registryServiceDefinition = $this
            ->getMockBuilder(Definition::class)
            ->getMock();
        $this->containerBuilder
            ->expects($this->once())
            ->method('getDefinition')
            ->with(PricesStrategyPass::STRATEGY_REGISTER)
            ->willReturn($registryServiceDefinition);
        $taggedServices = [
            'service.name.1' => [[]],
        ];
        $this->containerBuilder
            ->expects($this->once())
            ->method('findTaggedServiceIds')
            ->willReturn($taggedServices);
        $registryServiceDefinition->expects($this->never())->method('addMethodCall');
        $this->compilerPass->process($this->containerBuilder);
    }
}
