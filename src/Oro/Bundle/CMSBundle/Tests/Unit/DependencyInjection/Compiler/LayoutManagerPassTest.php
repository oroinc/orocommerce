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
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new LayoutManagerPass();
    }

    public function testProcessNoLayoutFactoryBuilderAndNoLayoutManager(): void
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);
    }

    public function testProcess(): void
    {
        $container = new ContainerBuilder();

        $container->register('oro_layout.layout_factory_builder', LayoutFactoryBuilder::class)
            ->setArguments([
                new Reference('oro_layout.processor.expression'),
                new Reference('oro_layout.cache.block_view_cache')
            ])
            ->addMethodCall('addExtension', [new Reference('oro_layout.extension')])
            ->addMethodCall('addExtension', [new Reference('oro_layout.theme_extension')])
            ->addMethodCall('setDefaultRenderer', ['%oro_layout.templating.default%']);
        $container->register('oro_layout.layout_manager', LayoutManager::class)
            ->setArguments([
                new Reference('oro_layout.layout_factory_builder'),
                new Reference('oro_layout.layout_context_holder'),
                new Reference('oro_layout.profiler.layout_data_collector')
            ]);

        $this->compiler->process($container);

        $expectedCmsLayoutFactoryBuilderDef = (new Definition(LayoutFactoryBuilder::class))
            ->setArguments([
                new Reference('oro_layout.processor.expression'),
                new Reference('oro_layout.cache.block_view_cache')
            ])
            ->addMethodCall('addExtension', [new Reference('oro_layout.extension')])
            ->addMethodCall('addExtension', [new Reference('oro_cms.layout_extension.content_widget')])
            ->addMethodCall('setDefaultRenderer', ['%oro_layout.templating.default%']);
        self::assertEquals(
            $expectedCmsLayoutFactoryBuilderDef,
            $container->getDefinition('oro_cms.layout_factory_builder')
        );

        $expectedCmsLayoutManagerDef = (new Definition(LayoutManager::class))
            ->setArguments([
                new Reference('oro_cms.layout_factory_builder'),
                new Reference('oro_layout.layout_context_holder'),
                new Reference('oro_layout.profiler.layout_data_collector')
            ]);
        self::assertEquals(
            $expectedCmsLayoutManagerDef,
            $container->getDefinition('oro_cms.layout_manager')
        );
    }
}
