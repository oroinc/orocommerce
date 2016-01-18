<?php

namespace OroB2B\Bundle\MenuBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use OroB2B\Bundle\MenuBundle\DependencyInjection\Compiler\FactoryExtensionsCompilerPass;

class FactoryExtensionsCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FactoryExtensionsCompilerPass
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

        $this->compilerPass = new FactoryExtensionsCompilerPass();
    }

    public function testServiceNotExists()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with(FactoryExtensionsCompilerPass::FACTORY_SERVICE_ID)
            ->willReturn(false);

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
            ->with(FactoryExtensionsCompilerPass::FACTORY_SERVICE_ID)
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(FactoryExtensionsCompilerPass::TAG_NAME)
            ->willReturn([]);

        $this->container->expects($this->never())
            ->method('getDefinition');

        $this->compilerPass->process($this->container);
    }

    public function testServiceExistsWithTaggedServices()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with(FactoryExtensionsCompilerPass::FACTORY_SERVICE_ID)
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(FactoryExtensionsCompilerPass::TAG_NAME)
            ->willReturn(['service' => ['class' => '\stdClass']]);

        $definition = $this->getMock('Symfony\Component\DependencyInjection\Definition');

        $this->container->expects($this->once())
            ->method('getDefinition')
            ->with(FactoryExtensionsCompilerPass::FACTORY_SERVICE_ID)
            ->willReturn($definition);

        $definition->expects($this->once())
            ->method('addMethodCall')
            ->with('addExtension', $this->isType('array'));

        $this->compilerPass->process($this->container);
    }
}
