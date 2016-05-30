<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use OroB2B\Bundle\WebsiteBundle\DependencyInjection\CompilerPass\TranslationStrategyPass;

class TranslationStrategyPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcessNoStrategyProvider()
    {
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->setMethods(['hasDefinition', 'addMethodCall'])
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects($this->once())
            ->method('hasDefinition')
            ->with(TranslationStrategyPass::STRATEGY_PROVIDER)
            ->willReturn(false);
        $container->expects($this->never())
            ->method('addMethodCall');

        $compilerPass = new TranslationStrategyPass();
        $compilerPass->process($container);
    }

    public function testProcessWithStrategyProvider()
    {
        $compositeStrategyDefinition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();

        $strategyProviderDefinition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();
        $strategyProviderDefinition->expects($this->once())
            ->method('addMethodCall')
            ->with('setStrategy', [$compositeStrategyDefinition]);

        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects($this->once())
            ->method('hasDefinition')
            ->with(TranslationStrategyPass::STRATEGY_PROVIDER)
            ->willReturn(true);
        $container->expects($this->any())
            ->method('getDefinition')
            ->with()
            ->willReturnMap([
                [TranslationStrategyPass::STRATEGY_PROVIDER, $strategyProviderDefinition],
                [TranslationStrategyPass::COMPOSITE_STRATEGY, $compositeStrategyDefinition]
            ]);

        $compilerPass = new TranslationStrategyPass();
        $compilerPass->process($container);
    }
}
