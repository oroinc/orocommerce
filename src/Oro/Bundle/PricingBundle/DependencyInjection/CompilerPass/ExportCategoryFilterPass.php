<?php

namespace Oro\Bundle\PricingBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds price attribute category filter support
 */
class ExportCategoryFilterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $registry = 'oro_catalog.datagrid.export.category_filter.registry';

        if ($container->hasDefinition($registry)) {
            $priceAttributeCategoryFilter = 'oro_pricing.datagrid.export.category_filter.price_attribute_product_price';
            $container
                ->getDefinition($registry)
                ->addMethodCall('add', [new Reference($priceAttributeCategoryFilter)]);
        }
    }
}
