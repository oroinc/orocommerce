<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\WebCatalogBundle\DependencyInjection\Compiler\WebCatalogDependenciesCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class WebCatalogDependenciesCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcessNoDefinition()
    {
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_product.provider.content_variant_segment_provider')
            ->willReturn(false);
        $container->expects($this->never())
            ->method('getDefinition');

        $compilerPass = new WebCatalogDependenciesCompilerPass();
        $compilerPass->process($container);
    }

    public function testProcess()
    {
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_product.provider.content_variant_segment_provider')
            ->willReturn(true);

        $definition = $this->createMock(Definition::class);
        $definition->expects($this->once())
            ->method('addMethodCall')
            ->with(
                'setWebCatalogUsageProvider',
                [new Reference('oro_web_catalog.provider.web_catalog_usage_provider')]
            );
        $container->expects($this->once())
            ->method('getDefinition')
            ->with('oro_product.provider.content_variant_segment_provider')
            ->willReturn($definition);

        $compilerPass = new WebCatalogDependenciesCompilerPass();
        $compilerPass->process($container);
    }
}
