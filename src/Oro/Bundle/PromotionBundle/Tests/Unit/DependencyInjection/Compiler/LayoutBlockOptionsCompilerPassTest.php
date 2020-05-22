<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\PromotionBundle\DependencyInjection\Compiler\LayoutBlockOptionsCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class LayoutBlockOptionsCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var LayoutBlockOptionsCompilerPass
     */
    private $compilerPass;

    protected function setUp(): void
    {
        $this->compilerPass = new LayoutBlockOptionsCompilerPass();
    }

    public function testProcessWithoutDefinition()
    {
        /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject $container */
        $container = $this->createMock(ContainerBuilder::class);

        $container->expects($this->exactly(2))
            ->method('hasDefinition')
            ->withConsecutive(
                [LayoutBlockOptionsCompilerPass::SHOPPING_LIST_LINE_ITEMS_BLOCK_SERVICE],
                [LayoutBlockOptionsCompilerPass::CHECKOUT_LINE_ITEMS_BLOCK_SERVICE]
            )
            ->willReturn(false);
        $container->expects($this->never())
            ->method('getDefinition');

        $this->compilerPass->process($container);
    }

    public function testProcess()
    {
        /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject $container */
        $container = $this->createMock(ContainerBuilder::class);

        $options = [
            ['test1' => ['required' => true]]
        ];
        $methods = [
            ['setOptionsConfig', [$options]]
        ];
        $shoppingListDefinition = $this->getDefinitionMock($methods, $options);
        $checkoutDefinition = $this->getDefinitionMock($methods, $options);

        $container->expects($this->exactly(2))
            ->method('hasDefinition')
            ->withConsecutive(
                [LayoutBlockOptionsCompilerPass::SHOPPING_LIST_LINE_ITEMS_BLOCK_SERVICE],
                [LayoutBlockOptionsCompilerPass::CHECKOUT_LINE_ITEMS_BLOCK_SERVICE]
            )
            ->willReturn(true);
        $container->expects($this->exactly(2))
            ->method('getDefinition')
            ->withConsecutive(
                [LayoutBlockOptionsCompilerPass::SHOPPING_LIST_LINE_ITEMS_BLOCK_SERVICE],
                [LayoutBlockOptionsCompilerPass::CHECKOUT_LINE_ITEMS_BLOCK_SERVICE]
            )
            ->willReturnOnConsecutiveCalls(
                $shoppingListDefinition,
                $checkoutDefinition
            );

        $this->compilerPass->process($container);
    }

    /**
     * @param array $methods
     * @param array $options
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function getDefinitionMock(array $methods, array $options): \PHPUnit\Framework\MockObject\MockObject
    {
        $shoppingListDefinition = $this->createMock(Definition::class);
        $shoppingListDefinition->expects($this->once())
            ->method('getMethodCalls')
            ->willReturn($methods);
        $shoppingListDefinition->expects($this->once())
            ->method('removeMethodCall')
            ->with('setOptionsConfig');
        $shoppingListDefinition->expects($this->once())
            ->method('addMethodCall')
            ->with(
                'setOptionsConfig',
                [array_merge($options, ['lineItemDiscounts' => ['required' => false]])]
            );

        return $shoppingListDefinition;
    }
}
