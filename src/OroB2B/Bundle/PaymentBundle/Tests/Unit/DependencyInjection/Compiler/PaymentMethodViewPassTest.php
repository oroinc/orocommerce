<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\Compiler\PaymentMethodViewPass;

class PaymentMethodViewPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentMethodViewPass
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

        $this->compilerPass = new PaymentMethodViewPass();
    }

    public function tearDown()
    {
        unset($this->compilerPass, $this->containerBuilder);
    }

    public function testProcessRegistryDoesNotExist()
    {
        $this->containerBuilder
            ->expects($this->once())
            ->method('hasDefinition')
            ->with(PaymentMethodViewPass::REGISTRY_SERVICE)
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
            ->with(PaymentMethodViewPass::REGISTRY_SERVICE)
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
            ->with(PaymentMethodViewPass::REGISTRY_SERVICE)
            ->willReturn(true);

        $registryServiceDefinition = $this->getMock('Symfony\Component\DependencyInjection\Definition');

        $this->containerBuilder
            ->expects($this->once())
            ->method('getDefinition')
            ->with(PaymentMethodViewPass::REGISTRY_SERVICE)
            ->willReturn($registryServiceDefinition);

        $taggedServices = [
            'service.name.1' => [],
            'service.name.2' => [],
        ];

        $this->containerBuilder
            ->expects($this->once())
            ->method('findTaggedServiceIds')
            ->willReturn($taggedServices);

        $registryServiceDefinition
            ->expects($this->exactly(2))
            ->method('addMethodCall')
            ->withConsecutive(
                ['addPaymentMethodView', [new Reference('service.name.1')]],
                ['addPaymentMethodView', [new Reference('service.name.2')]]
            );

        $this->compilerPass->process($this->containerBuilder);
    }
}
