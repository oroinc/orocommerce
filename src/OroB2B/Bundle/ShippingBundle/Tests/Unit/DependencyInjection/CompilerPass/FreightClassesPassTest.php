<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use OroB2B\Bundle\ShippingBundle\DependencyInjection\CompilerPass\FreightClassesPass;

class FreightClassesPassTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ContainerBuilder */
    protected $container;

    /** @var FreightClassesPass */
    protected $compilerPass;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->compilerPass = new FreightClassesPass();
    }

    public function testProcessServiceNotExists()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with(FreightClassesPass::EXTENSION_SERVICE_ID)
            ->willReturn(false);

        $this->container->expects($this->never())->method('getDefinition');
        $this->container->expects($this->never())->method('findTaggedServiceIds');

        $this->compilerPass->process($this->container);
    }

    public function testProcessServiceExistsProvidersNotExists()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with(FreightClassesPass::EXTENSION_SERVICE_ID)
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(FreightClassesPass::PROVIDER_TAG)
            ->willReturn([]);

        $this->container->expects($this->never())->method('getDefinition');

        $this->compilerPass->process($this->container);
    }

    public function testProcess()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with(FreightClassesPass::EXTENSION_SERVICE_ID)
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(FreightClassesPass::PROVIDER_TAG)
            ->willReturn(['provider_service' => ['class' => '\stdClass']]);

        $definition = $this->getMock('Symfony\Component\DependencyInjection\Definition');

        $this->container->expects($this->exactly(2))
            ->method('getDefinition')
            ->willReturnMap(
                [
                    [FreightClassesPass::EXTENSION_SERVICE_ID, $definition],
                    ['provider_service', $this->getMock('Symfony\Component\DependencyInjection\Definition')]
                ]
            );

        $definition->expects($this->once())
            ->method('addMethodCall')
            ->with('addExtension', $this->isType('array'));

        $this->compilerPass->process($this->container);
    }
}
