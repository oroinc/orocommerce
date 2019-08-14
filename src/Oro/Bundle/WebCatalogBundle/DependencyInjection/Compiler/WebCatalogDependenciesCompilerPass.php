<?php

namespace Oro\Bundle\WebCatalogBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Initialize web catalog dependent bundles
 */
class WebCatalogDependenciesCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('oro_product.provider.content_variant_segment_provider')) {
            $container->getDefinition('oro_product.provider.content_variant_segment_provider')
                ->addMethodCall(
                    'setWebCatalogUsageProvider',
                    [new Reference('oro_web_catalog.provider.web_catalog_usage_provider')]
                );
        }
    }
}
