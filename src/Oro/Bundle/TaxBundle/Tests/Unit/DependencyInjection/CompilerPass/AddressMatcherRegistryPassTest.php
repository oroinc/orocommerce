<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\TaxBundle\DependencyInjection\CompilerPass\AddressMatcherRegistryPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AddressMatcherRegistryPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AddressMatcherRegistryPass
     */
    protected $compilerPass;

    /**
     * @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $containerBuilder;

    public function setUp()
    {
        $this->containerBuilder = $this
            ->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();
        $this->compilerPass = new AddressMatcherRegistryPass();
    }

    public function testProcessRegistryDoesNotExist()
    {
        $this->containerBuilder
            ->expects($this->once())
            ->method('hasDefinition')
            ->with(AddressMatcherRegistryPass::SERVICE)
            ->willReturn(false);

        $this->containerBuilder
            ->expects($this->never())
            ->method('getDefinition');

        $this->containerBuilder
            ->expects($this->never())
            ->method('findTaggedServiceIds');

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcessNoTaggedServicesFound()
    {
        $this->containerBuilder
            ->expects($this->once())
            ->method('hasDefinition')
            ->with(AddressMatcherRegistryPass::SERVICE)
            ->willReturn(true);

        $this->containerBuilder
            ->expects($this->once())
            ->method('findTaggedServiceIds')
            ->willReturn([]);

        $this->containerBuilder
            ->expects($this->never())
            ->method('getDefinition');

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcessWithTaggedServices()
    {
        $this->containerBuilder
            ->expects($this->once())
            ->method('hasDefinition')
            ->with(AddressMatcherRegistryPass::SERVICE)
            ->willReturn(true);

        $registryServiceDefinition = $this
            ->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->getMock();

        $this->containerBuilder
            ->expects($this->once())
            ->method('getDefinition')
            ->with(AddressMatcherRegistryPass::SERVICE)
            ->willReturn($registryServiceDefinition);

        $taggedServices = [
            'service.name.1' => [['type' => 'address_matcher']],
            'service.name.2' => [['type' => 'address_matcher']],
            'service.name.3' => [['type' => 'address_matcher']],
            'service.name.4' => [['type' => 'address_matcher']],
        ];

        $this->containerBuilder
            ->expects($this->once())
            ->method('findTaggedServiceIds')
            ->willReturn($taggedServices);

        $registryServiceDefinition
            ->expects($this->exactly(4))
            ->method('addMethodCall')
            ->withConsecutive(
                ['addMatcher', ['address_matcher', new Reference('service.name.1')]],
                ['addMatcher', ['address_matcher', new Reference('service.name.2')]],
                ['addMatcher', ['address_matcher', new Reference('service.name.3')]],
                ['addMatcher', ['address_matcher', new Reference('service.name.4')]]
            );

        $this->compilerPass->process($this->containerBuilder);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage "type" is missing for "oro_tax.address_matcher" tag at "service.name.4" service
     */
    public function testProcessTypeIsMissing()
    {
        $this->containerBuilder
            ->expects($this->once())
            ->method('hasDefinition')
            ->with(AddressMatcherRegistryPass::SERVICE)
            ->willReturn(true);

        $registryServiceDefinition = $this
            ->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->getMock();

        $this->containerBuilder
            ->expects($this->once())
            ->method('getDefinition')
            ->with(AddressMatcherRegistryPass::SERVICE)
            ->willReturn($registryServiceDefinition);

        $taggedServices = [
            'service.name.4' => [[]],
        ];

        $this->containerBuilder
            ->expects($this->once())
            ->method('findTaggedServiceIds')
            ->willReturn($taggedServices);

        $registryServiceDefinition->expects($this->never())->method('addMethodCall');

        $this->compilerPass->process($this->containerBuilder);
    }
}
