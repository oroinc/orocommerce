<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass\AttributeBlockTypeMapperPass;

class AttributeBlockTypeMapperPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $registry = $this->getMockBuilder(Definition::class)->getMock();

        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this->getMockBuilder(ContainerBuilder::class)->getMock();
        $container->expects($this->once())
            ->method('getDefinition')
            ->with(AttributeBlockTypeMapperPass::REGISTRY_SERVICE)
            ->willReturn($registry);

        $container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(AttributeBlockTypeMapperPass::TAG)
            ->willReturn(['service1' => true, 'service2' => true]);

        $registry->expects($this->exactly(2))
            ->method('addMethodCall')
            ->willReturnMap([
                ['addProvider', [new Reference('service1')], $registry],
                ['addProvider', [new Reference('service2')], $registry],
            ]);

        $compilerPass = new AttributeBlockTypeMapperPass();
        $compilerPass->process($container);
    }

    public function testProcessSkip()
    {
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this->getMockBuilder(ContainerBuilder::class)->getMock();
        $container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(AttributeBlockTypeMapperPass::TAG)
            ->willReturn([]);

        $container->expects($this->never())
            ->method('getDefinition');

        $compilerPass = new AttributeBlockTypeMapperPass();
        $compilerPass->process($container);
    }
}
