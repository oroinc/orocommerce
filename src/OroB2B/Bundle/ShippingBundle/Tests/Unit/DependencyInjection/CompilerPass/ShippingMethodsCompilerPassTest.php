<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use OroB2B\Bundle\ShippingBundle\DependencyInjection\CompilerPass\ShippingMethodsCompilerPass;

class ShippingMethodsCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ShippingMethodsCompilerPass
     */
    protected $compilerPass;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContainerBuilder
     */
    protected $container;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->container = $this
            ->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();

        $this->compilerPass = new ShippingMethodsCompilerPass();
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        unset($this->container, $this->compilerPass);
    }

    public function testServiceNotExists()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with($this->equalTo(ShippingMethodsCompilerPass::SERVICE))
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
            ->with($this->equalTo(ShippingMethodsCompilerPass::SERVICE))
            ->will($this->returnValue(true));

        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with($this->equalTo(ShippingMethodsCompilerPass::TAG))
            ->will($this->returnValue([]));

        $this->container->expects($this->never())
            ->method('getDefinition');

        $this->compilerPass->process($this->container);
    }

    public function testServiceExistsWithTaggedService()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with($this->equalTo(ShippingMethodsCompilerPass::SERVICE))
            ->will($this->returnValue(true));

        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with($this->equalTo(ShippingMethodsCompilerPass::TAG))
            ->will($this->returnValue(['service' => ['class' => '\stdClass']]));

        $definition = $this->getMock('Symfony\Component\DependencyInjection\Definition');

        $this->container->expects($this->once())
            ->method('getDefinition')
            ->with($this->equalTo(ShippingMethodsCompilerPass::SERVICE))
            ->will($this->returnValue($definition));

        $definition->expects($this->once())
            ->method('addMethodCall')
            ->with('addShippingMethod', $this->isType('array'));

        $this->compilerPass->process($this->container);
    }
}
