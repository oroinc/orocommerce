<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

abstract class AbstractPaymentMethodProvidersPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CompilerPassInterface
     */
    protected $compilerPass;

    /**
     * @var string
     */
    protected $serviceDefinition;

    /**
     * @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $containerBuilder;

    public function setUp()
    {
        $this->containerBuilder = $this
            ->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();
    }

    public function tearDown()
    {
        unset($this->compilerPass, $this->containerBuilder);
    }

    public function testProcessRegistryDoesNotExist()
    {
        $this->containerBuilder
            ->expects(static::once())
            ->method('hasDefinition')
            ->with($this->serviceDefinition)
            ->willReturn(false);

        $this->containerBuilder
            ->expects(static::never())
            ->method('getDefinition');

        $this->containerBuilder
            ->expects(static::never())
            ->method('findTaggedServiceIds');

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcessNoTaggedServicesFound()
    {
        $this->containerBuilder
            ->expects(static::once())
            ->method('hasDefinition')
            ->with($this->serviceDefinition)
            ->willReturn(true);

        $this->containerBuilder
            ->expects(static::once())
            ->method('findTaggedServiceIds')
            ->willReturn([]);

        $this->containerBuilder
            ->expects(static::never())
            ->method('getDefinition');

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcessWithTaggedServices()
    {
        $this->containerBuilder
            ->expects(static::once())
            ->method('hasDefinition')
            ->with($this->serviceDefinition)
            ->willReturn(true);

        $registryServiceDefinition = $this->createMock('Symfony\Component\DependencyInjection\Definition');

        $this->containerBuilder
            ->expects(static::once())
            ->method('getDefinition')
            ->with($this->serviceDefinition)
            ->willReturn($registryServiceDefinition);

        $taggedServices = [
            'service.name.1' => [],
            'service.name.2' => [],
        ];

        $this->containerBuilder
            ->expects(static::once())
            ->method('findTaggedServiceIds')
            ->willReturn($taggedServices);

        $registryServiceDefinition
            ->expects(static::exactly(2))
            ->method('addMethodCall')
            ->withConsecutive(
                ['addProvider', [new Reference('service.name.1')]],
                ['addProvider', [new Reference('service.name.2')]]
            );

        $this->compilerPass->process($this->containerBuilder);
    }
}
