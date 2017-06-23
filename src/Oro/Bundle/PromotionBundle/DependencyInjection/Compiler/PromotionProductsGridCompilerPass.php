<?php

namespace Oro\Bundle\PromotionBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This compiler pass enabled product collection datagrid listener for promotion product collection grid.
 * If segment's parameters (definition, included products, excluded products) are passed via grid parameters or
 * request then they will be used to filter grid data based on these parameters.
 */
class PromotionProductsGridCompilerPass implements CompilerPassInterface
{
    const PRODUCT_COLLECTION_DATAGRID_LISTENER = 'oro_product.event_listener.product_collection_datagrid';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container
            ->getDefinition(self::PRODUCT_COLLECTION_DATAGRID_LISTENER)
            ->addTag('kernel.event_listener', [
                'event' => 'oro_datagrid.datagrid.build.after.promotion-products-collection-grid',
                'method' => 'onBuildAfter'
            ]);
    }
}
