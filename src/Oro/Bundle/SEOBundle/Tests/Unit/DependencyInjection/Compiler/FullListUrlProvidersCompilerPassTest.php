<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\SEOBundle\DependencyInjection\Compiler\FullListUrlProvidersCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;

class FullListUrlProvidersCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FullListUrlProvidersCompilerPass
     */
    private $compilerPass;

    /**
     * @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    private $containerBuilder;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->containerBuilder = $this
            ->getMockBuilder(ContainerBuilder::class)
            ->getMock();

        $this->compilerPass = new FullListUrlProvidersCompilerPass();
    }

    public function testProcessCompositeDoesNotExist()
    {
        $this->containerBuilder
            ->expects($this->once())
            ->method('hasDefinition')
            ->with(FullListUrlProvidersCompilerPass::PROVIDER_REGISTRY)
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
            ->with(FullListUrlProvidersCompilerPass::PROVIDER_REGISTRY)
            ->willReturn(true);

        $this->containerBuilder
            ->expects($this->any())
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
            ->with(FullListUrlProvidersCompilerPass::PROVIDER_REGISTRY)
            ->willReturn(true);

        $compositeServiceDefinition = $this->createMock(Definition::class);

        $this->containerBuilder
            ->expects($this->once())
            ->method('getDefinition')
            ->with(FullListUrlProvidersCompilerPass::PROVIDER_REGISTRY)
            ->willReturn($compositeServiceDefinition);

        $taggedServices = [
            'service.name.1' => [['alias' => 'taggedService1Alias']],
            'service.name.2' => [['alias' => 'taggedService2Alias']],
        ];

        $this->containerBuilder
            ->expects($this->any())
            ->method('findTaggedServiceIds')
            ->willReturn($taggedServices);

        $compositeServiceDefinition
            ->expects($this->once())
            ->method('replaceArgument')
            ->with(
                0,
                [
                    'taggedService1Alias' => new Reference('service.name.1'),
                    'taggedService2Alias' => new Reference('service.name.2')
                ]
            );

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcessWithTaggedServicesWithoutAlias()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Could not retrieve "alias" attribute for "service.name.1"');

        $this->containerBuilder
            ->expects($this->once())
            ->method('hasDefinition')
            ->with(FullListUrlProvidersCompilerPass::PROVIDER_REGISTRY)
            ->willReturn(true);

        $taggedServices = [
            'service.name.1' => ['not_alias' => 'test'],
        ];

        $this->containerBuilder
            ->expects($this->any())
            ->method('findTaggedServiceIds')
            ->willReturn($taggedServices);

        $this->compilerPass->process($this->containerBuilder);
    }
}
