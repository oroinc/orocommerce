<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\RFPBundle\DependencyInjection\CompilerPass\OrderBundlePass;

class OrderBundlePassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CompilerPassInterface
     */
    protected $compilerPass;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContainerBuilder
     */
    protected $containerBuilder;

    protected function setUp()
    {
        $this->containerBuilder = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->compilerPass = new OrderBundlePass();
    }

    public function testProcessWithoutOrderBundle()
    {
        $this->containerBuilder->expects($this->once())->method('hasParameter')->willReturn(false);
        $this->containerBuilder->expects($this->never())->method('hasDefinition');

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcessWithOrderBundleWithoutDefinition()
    {
        $this->containerBuilder->expects($this->once())->method('hasParameter')->willReturn(true);
        $this->containerBuilder->expects($this->once())->method('hasDefinition')->willReturn(false);
        $this->containerBuilder->expects($this->never())->method('getDefinition');

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcessWithOrderBundle()
    {
        $this->containerBuilder->expects($this->once())->method('hasParameter')->willReturn(true);
        $this->containerBuilder->expects($this->once())->method('hasDefinition')->willReturn(true);

        $definition = new Definition();
        $this->containerBuilder->expects($this->once())->method('getDefinition')->willReturn($definition);

        $this->compilerPass->process($this->containerBuilder);

        $this->assertEquals(
            [['setSectionProvider', [new Reference('oro_order.form.section.provider')]]],
            $definition->getMethodCalls()
        );
    }
}
