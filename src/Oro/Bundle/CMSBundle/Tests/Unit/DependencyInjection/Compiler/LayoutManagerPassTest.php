<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\CMSBundle\DependencyInjection\Compiler\LayoutManagerPass;
use Oro\Bundle\LayoutBundle\Layout\LayoutManager;
use Oro\Component\Layout\LayoutFactoryBuilder;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class LayoutManagerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var LayoutManagerPass */
    private $compilerPass;

    /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject */
    protected $containerBuilder;

    protected function setUp(): void
    {
        $this->compilerPass = new LayoutManagerPass();

        $this->containerBuilder = $this->createMock(ContainerBuilder::class);
    }

    public function testProcessNoLayoutFactoryBuilderDefinition(): void
    {
        $this->containerBuilder->expects($this->exactly(2))
            ->method('hasDefinition')
            ->willReturnMap(
                [
                    ['oro_layout.layout_factory_builder', false],
                    ['oro_layout.layout_manager', true],
                ]
            );
        $this->containerBuilder->expects($this->once())
            ->method('findDefinition')
            ->with('oro_layout.layout_manager')
            ->willReturn($this->getLayoutManagerDefinition('oro_layout.layout_factory_builder'));

        $this->containerBuilder->expects($this->once())
            ->method('setDefinition')
            ->with(
                'oro_cms.layout_manager',
                $this->getLayoutManagerDefinition('oro_cms.layout_factory_builder')
            );

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcessNoLayoutManagerDefinition(): void
    {
        $this->containerBuilder->expects($this->exactly(2))
            ->method('hasDefinition')
            ->willReturnMap(
                [
                    ['oro_layout.layout_factory_builder', true],
                    ['oro_layout.layout_manager', false],
                ]
            );
        $this->containerBuilder->expects($this->once())
            ->method('findDefinition')
            ->with('oro_layout.layout_factory_builder')
            ->willReturn($this->getLayoutFactoryBuilderDefinition('oro_layout.theme_extension'));

        $this->containerBuilder->expects($this->once())
            ->method('setDefinition')
            ->with(
                'oro_cms.layout_factory_builder',
                $this->getLayoutFactoryBuilderDefinition('oro_cms.layout_extension.content_widget')
            );

        $this->compilerPass->process($this->containerBuilder);
    }

    public function testProcess(): void
    {
        $this->containerBuilder->expects($this->exactly(2))
            ->method('hasDefinition')
            ->willReturnMap(
                [
                    ['oro_layout.layout_factory_builder', true],
                    ['oro_layout.layout_manager', true],
                ]
            );
        $this->containerBuilder->expects($this->exactly(2))
            ->method('findDefinition')
            ->willReturnMap(
                [
                    [
                        'oro_layout.layout_factory_builder',
                        $this->getLayoutFactoryBuilderDefinition('oro_layout.theme_extension'),
                    ],
                    [
                        'oro_layout.layout_manager',
                        $this->getLayoutManagerDefinition('oro_layout.layout_factory_builder'),
                    ],
                ]
            );

        $this->containerBuilder->expects($this->exactly(2))
            ->method('setDefinition')
            ->withConsecutive(
                [
                    'oro_cms.layout_factory_builder',
                    $this->getLayoutFactoryBuilderDefinition('oro_cms.layout_extension.content_widget'),
                ],
                [
                    'oro_cms.layout_manager',
                    $this->getLayoutManagerDefinition('oro_cms.layout_factory_builder'),
                ]
            );

        $this->compilerPass->process($this->containerBuilder);
    }

    private function getLayoutFactoryBuilderDefinition(string $layoutExtension): Definition
    {
        $definition = new Definition(
            LayoutFactoryBuilder::class,
            [new Reference('oro_layout.processor.expression'), new Reference('oro_layout.cache.block_view_cache')]
        );
        $definition->addMethodCall('addExtension', [new Reference('oro_layout.extension')]);
        $definition->addMethodCall('addExtension', [new Reference($layoutExtension)]);
        $definition->addMethodCall('setDefaultRenderer', ['%oro_layout.templating.default%']);

        return $definition;
    }

    private function getLayoutManagerDefinition(string $layoutFactoryBuilder): Definition
    {
        return new Definition(
            LayoutManager::class,
            [
                new Reference($layoutFactoryBuilder),
                new Reference('oro_layout.layout_context_holder'),
                new Reference('oro_layout.profiler.layout_data_collector'),
            ]
        );
    }
}
