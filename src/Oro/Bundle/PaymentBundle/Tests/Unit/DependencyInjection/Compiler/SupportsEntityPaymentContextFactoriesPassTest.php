<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\PaymentBundle\DependencyInjection\Compiler\SupportsEntityPaymentContextFactoriesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class SupportsEntityPaymentContextFactoriesPassTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SupportsEntityPaymentContextFactoriesPass
     */
    private $compilerPass;

    /**
     * @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    private $containerBuilder;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->containerBuilder = $this
            ->getMockBuilder(ContainerBuilder::class)
            ->getMock();

        $this->compilerPass = new SupportsEntityPaymentContextFactoriesPass();
    }

    public function testProcessCompositeDoesNotExist()
    {
        $this->containerBuilder
            ->expects($this->once())
            ->method('hasDefinition')
            ->with(SupportsEntityPaymentContextFactoriesPass::COMPOSITE_SERVICE)
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
            ->with(SupportsEntityPaymentContextFactoriesPass::COMPOSITE_SERVICE)
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
            ->with(SupportsEntityPaymentContextFactoriesPass::COMPOSITE_SERVICE)
            ->willReturn(true);

        $compositeServiceDefinition = $this->createMock(Definition::class);

        $this->containerBuilder
            ->expects($this->once())
            ->method('getDefinition')
            ->with(SupportsEntityPaymentContextFactoriesPass::COMPOSITE_SERVICE)
            ->willReturn($compositeServiceDefinition);

        $taggedServices = [
            'service.name.1' => [[]],
            'service.name.2' => [[]],
        ];

        $this->containerBuilder
            ->expects($this->once())
            ->method('findTaggedServiceIds')
            ->willReturn($taggedServices);

        $compositeServiceDefinition
            ->expects($this->once())
            ->method('replaceArgument')
            ->with(0, [new Reference('service.name.1'), new Reference('service.name.2')]);

        $this->compilerPass->process($this->containerBuilder);
    }
}
