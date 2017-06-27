<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\PromotionBundle\DependencyInjection\Compiler\ShoppingListBlockOptionsCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ShoppingListBlockOptionsCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ShoppingListBlockOptionsCompilerPass
     */
    private $compilerPass;

    protected function setUp()
    {
        $this->compilerPass = new ShoppingListBlockOptionsCompilerPass();
    }

    public function testProcessWithoutDefinition()
    {
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this->createMock(ContainerBuilder::class);

        $container->expects($this->once())
            ->method('hasDefinition')
            ->with(ShoppingListBlockOptionsCompilerPass::SHOPPING_LIST_LINE_ITEMS_BLOCK_SERVICE)
            ->willReturn(false);
        $container->expects($this->never())
            ->method('getDefinition');

        $this->compilerPass->process($container);
    }

    public function testProcess()
    {
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this->createMock(ContainerBuilder::class);

        $options = [
            ['test1' => ['required' => true]]
        ];
        $methods = [
            ['setOptionsConfig', [$options]]
        ];
        /** @var Definition|\PHPUnit_Framework_MockObject_MockObject $definition */
        $definition = $this->createMock(Definition::class);
        $definition->expects($this->once())
            ->method('getMethodCalls')
            ->willReturn($methods);
        $definition->expects($this->once())
            ->method('removeMethodCall')
            ->with('setOptionsConfig');
        $definition->expects($this->once())
            ->method('addMethodCall')
            ->with(
                'setOptionsConfig',
                [array_merge($options, ['lineItemDiscounts' => ['required' => false]])]
            );

        $container->expects($this->once())
            ->method('hasDefinition')
            ->with(ShoppingListBlockOptionsCompilerPass::SHOPPING_LIST_LINE_ITEMS_BLOCK_SERVICE)
            ->willReturn(true);
        $container->expects($this->once())
            ->method('getDefinition')
            ->with(ShoppingListBlockOptionsCompilerPass::SHOPPING_LIST_LINE_ITEMS_BLOCK_SERVICE)
            ->willReturn($definition);

        $this->compilerPass->process($container);
    }
}
