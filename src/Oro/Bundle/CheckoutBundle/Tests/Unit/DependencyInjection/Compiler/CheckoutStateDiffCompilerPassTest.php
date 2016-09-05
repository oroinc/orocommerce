<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\CheckoutBundle\DependencyInjection\Compiler\CheckoutStateDiffCompilerPass;

class CheckoutStateDiffCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CheckoutStateDiffCompilerPass
     */
    protected $compilerPass;

    /**
     * @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $containerBuilder;

    /**
     * @var Definition|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $definition;

    public function setUp()
    {
        $this->compilerPass = new CheckoutStateDiffCompilerPass();
        $this->definition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();
        $this->containerBuilder = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function tearDown()
    {
        unset($this->compilerPass, $this->definition, $this->containerBuilder);
    }

    public function testProcess()
    {
        $this->containerBuilder->expects($this->once())
            ->method('has')
            ->with(CheckoutStateDiffCompilerPass::CHECKOUT_STATE_DIFF_REGISTRY)
            ->willReturn(true);

        $this->containerBuilder->expects($this->once())
            ->method('getDefinition')
            ->with(CheckoutStateDiffCompilerPass::CHECKOUT_STATE_DIFF_REGISTRY)
            ->will($this->returnValue($this->definition));

        $mappers = [
            'mapper1' => [['priority' => 20]],
            'mapper2' => [['priority' => 30]],
            'mapper3' => [['priority' => 20]],
            'mapper4' => [],
        ];

        $this->containerBuilder->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(CheckoutStateDiffCompilerPass::CHECKOUT_STATE_DIFF_MAPPER_TAG)
            ->will($this->returnValue($mappers));

        $this->definition->expects($this->at(0))
            ->method('addMethodCall')
            ->with('addMapper', array(new Reference('mapper4')));

        $this->definition->expects($this->at(1))
            ->method('addMethodCall')
            ->with('addMapper', array(new Reference('mapper1')));

        $this->definition->expects($this->at(2))
            ->method('addMethodCall')
            ->with('addMapper', array(new Reference('mapper3')));

        $this->definition->expects($this->at(3))
            ->method('addMethodCall')
            ->with('addMapper', array(new Reference('mapper2')));

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcessNoManagerDefinition()
    {
        $this->containerBuilder->expects($this->once())
            ->method('has')
            ->with(CheckoutStateDiffCompilerPass::CHECKOUT_STATE_DIFF_REGISTRY)
            ->willReturn(false);

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcessNoMappers()
    {
        $this->containerBuilder->expects($this->once())
            ->method('has')
            ->with(CheckoutStateDiffCompilerPass::CHECKOUT_STATE_DIFF_REGISTRY)
            ->willReturn(true);

        $this->containerBuilder->expects($this->once())
            ->method('getDefinition')
            ->with(CheckoutStateDiffCompilerPass::CHECKOUT_STATE_DIFF_REGISTRY)
            ->will($this->returnValue($this->definition));

        $mappers = [];

        $this->containerBuilder->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(CheckoutStateDiffCompilerPass::CHECKOUT_STATE_DIFF_MAPPER_TAG)
            ->will($this->returnValue($mappers));

        $this->compilerPass->process($this->containerBuilder);
    }
}
