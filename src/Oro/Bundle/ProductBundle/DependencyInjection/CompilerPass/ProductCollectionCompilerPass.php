<?php

namespace Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This compiler pass removes services which depend on web catalog functionality thus they have sense only when web
 * catalog functionality is available.
 */
class ProductCollectionCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('oro_web_catalog.provider.web_catalog_usage_provider')) {
            $container->removeDefinition('oro_product.form.type.extension.product_collection');
        }
    }
}
