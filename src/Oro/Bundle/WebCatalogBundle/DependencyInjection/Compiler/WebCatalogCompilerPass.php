<?php

namespace Oro\Bundle\WebCatalogBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class WebCatalogCompilerPass implements CompilerPassInterface
{
    const WEB_CATALOG_PAGE_TYPE_REGISTRY = 'oro_web_catalog.page_type.registry';
    const WEB_CATALOG_PAGE_TYPE_TAG = 'oro_web_catalog.page_type';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::WEB_CATALOG_PAGE_TYPE_REGISTRY)) {
            return;
        }

        $placeholderRegistryDefinition = $container->getDefinition(self::WEB_CATALOG_PAGE_TYPE_REGISTRY);
        $taggedPlaceholders = $container->findTaggedServiceIds(self::WEB_CATALOG_PAGE_TYPE_TAG);

        foreach ($taggedPlaceholders as $id => $tags) {
            $placeholderRegistryDefinition->addMethodCall('addPageType', [new Reference($id)]);
        }
    }
}
