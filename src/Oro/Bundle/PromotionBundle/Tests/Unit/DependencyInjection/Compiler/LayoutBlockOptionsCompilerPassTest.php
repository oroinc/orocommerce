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

        $container->expects($this->once())
            ->method('hasDefinition')
            ->with(LayoutBlockOptionsCompilerPass::CHECKOUT_LINE_ITEMS_BLOCK_SERVICE)
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
        $checkoutDefinition = $this->getDefinitionMock($methods, $options);

        $container->expects($this->once())
            ->method('hasDefinition')
            ->with(LayoutBlockOptionsCompilerPass::CHECKOUT_LINE_ITEMS_BLOCK_SERVICE)
            ->willReturn(true);
        $container->expects($this->once())
            ->method('getDefinition')
            ->with(LayoutBlockOptionsCompilerPass::CHECKOUT_LINE_ITEMS_BLOCK_SERVICE)
            ->willReturn($checkoutDefinition);

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
