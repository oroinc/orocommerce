<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use OroB2B\Bundle\OrderBundle\DependencyInjection\CompilerPass\SubtotalProviderPass;

class SubtotalProviderPassTest extends \PHPUnit_Framework_TestCase
{
    /** @var SubtotalProviderPass */
    protected $compilerPass;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ContainerBuilder */
    protected $container;

    protected function setUp()
    {
        $this->container = $this
            ->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();

        $this->compilerPass = new SubtotalProviderPass();
    }

    protected function tearDown()
    {
        unset($this->container, $this->compilerPass);
    }

    public function testServiceNotExists()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with($this->equalTo(SubtotalProviderPass::REGISTRY_SERVICE))
            ->will($this->returnValue(false));

        $this->container->expects($this->never())
            ->method('getDefinition');

        $this->container->expects($this->never())
            ->method('findTaggedServiceIds');

        $this->compilerPass->process($this->container);
    }

    public function testServiceExistsNotTaggedServices()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with($this->equalTo(SubtotalProviderPass::REGISTRY_SERVICE))
            ->will($this->returnValue(true));

        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with($this->equalTo(SubtotalProviderPass::TAG))
            ->will($this->returnValue([]));

        $this->container->expects($this->never())
            ->method('getDefinition');

        $this->compilerPass->process($this->container);
    }

    public function testServiceExistsWithTaggedServices()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with($this->equalTo(SubtotalProviderPass::REGISTRY_SERVICE))
            ->will($this->returnValue(true));

        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with($this->equalTo(SubtotalProviderPass::TAG))
            ->will($this->returnValue(['service' => ['class' => '\stdClass']]));

        $definition = $this->getMock('Symfony\Component\DependencyInjection\Definition');

        $this->container->expects($this->once())
            ->method('getDefinition')
            ->with($this->equalTo(SubtotalProviderPass::REGISTRY_SERVICE))
            ->will($this->returnValue($definition));

        $definition->expects($this->once())
            ->method('addMethodCall')
            ->with('addProvider', $this->isType('array'));

        $this->compilerPass->process($this->container);
    }
}
