<?php

namespace Oro\Bundle\CommerceMenuBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\CommerceMenuBundle\DependencyInjection\Compiler\ConditionExpressionLanguageProvidersCompilerPass;

class ConditionExpressionLanguageProvidersCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this->getMockBuilder(ContainerBuilder::class)->getMock();
        $container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(ConditionExpressionLanguageProvidersCompilerPass::TAG_NAME)
            ->willReturn([1 => 'provider_1', 2 => 'provider_2']);

        /** @var Definition|\PHPUnit_Framework_MockObject_MockObject $definition */
        $definition = $this->getMockBuilder(Definition::class)->disableOriginalConstructor()->getMock();
        $definition->expects($this->exactly(2))
            ->method('addMethodCall')
            ->willReturnMap([
                ['addProvider', [new Reference(1)], $definition],
                ['addProvider', [new Reference(2)], $definition],
            ]);

        $container->expects($this->once())
            ->method('getDefinition')
            ->with(ConditionExpressionLanguageProvidersCompilerPass::CONDITION_SERVICE_ID)
            ->willReturn($definition);

        $compilerPass = new ConditionExpressionLanguageProvidersCompilerPass();
        $compilerPass->process($container);
    }
}
