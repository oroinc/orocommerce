<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use OroB2B\Bundle\ProductBundle\DependencyInjection\CompilerPass\DefaultProductUnitProvidersCompilerPass;

class DefaultProductUnitProvidersCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DefaultProductUnitProvidersCompilerPass
     */
    protected $compilerPass;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContainerBuilder
     */
    protected $container;

    protected function setUp()
    {
        $this->container = $this
            ->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();

        $this->compilerPass = new DefaultProductUnitProvidersCompilerPass();
    }

    protected function tearDown()
    {
        unset($this->container, $this->compilerPass);
    }

    public function testServiceNotExists()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with($this->equalTo(DefaultProductUnitProvidersCompilerPass::SERVICE))
            ->will($this->returnValue(false));

        $this->container->expects($this->never())
            ->method('getDefinition');

        $this->container->expects($this->never())
            ->method('findTaggedServiceIds');

        $this->compilerPass->process($this->container);
    }

    public function testServiceExistsNotTaggedService()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with($this->equalTo(DefaultProductUnitProvidersCompilerPass::SERVICE))
            ->will($this->returnValue(true));

        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with($this->equalTo(DefaultProductUnitProvidersCompilerPass::TAG))
            ->will($this->returnValue([]));

        $this->container->expects($this->never())
            ->method('getDefinition');

        $this->compilerPass->process($this->container);
    }

    public function testServiceExistsWithTaggedService()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with($this->equalTo(DefaultProductUnitProvidersCompilerPass::SERVICE))
            ->will($this->returnValue(true));

        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with($this->equalTo(DefaultProductUnitProvidersCompilerPass::TAG))
            ->will($this->returnValue(['service' => ['class' => '\stdClass']]));

        $definition = $this->getMock('Symfony\Component\DependencyInjection\Definition');

        $this->container->expects($this->once())
            ->method('getDefinition')
            ->with($this->equalTo(DefaultProductUnitProvidersCompilerPass::SERVICE))
            ->will($this->returnValue($definition));

        $definition->expects($this->once())
            ->method('addMethodCall')
            ->with('addProvider', $this->isType('array'));

        $this->compilerPass->process($this->container);
    }

    public function testServiceExistsWithMultipleTaggedServices()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with($this->equalTo(DefaultProductUnitProvidersCompilerPass::SERVICE))
            ->will($this->returnValue(true));

        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with($this->equalTo(DefaultProductUnitProvidersCompilerPass::TAG))
            ->will($this->returnValue([
                'lowPriority' => ['class' => '\stdClass', ['priority' => 0]],
                'highPriority' => ['class' => '\stdClass', ['priority' => 10]]
            ]));

        $definition = $this->getMock('Symfony\Component\DependencyInjection\Definition');

        $this->container->expects($this->once())
            ->method('getDefinition')
            ->with($this->equalTo(DefaultProductUnitProvidersCompilerPass::SERVICE))
            ->will($this->returnValue($definition));

        $highReference = new Reference('highPriority');
        $definition->expects($this->at(0))
            ->method('addMethodCall')
            ->with('addProvider', [$highReference]);

        $lowReference = new Reference('lowPriority');
        $definition->expects($this->at(1))
            ->method('addMethodCall')
            ->with('addProvider', [$lowReference]);

        $this->compilerPass->process($this->container);
    }
}
