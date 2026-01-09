<?php

namespace Oro\Bundle\InventoryBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Compiler pass that registers class migrations for inventory level entities.
 *
 * Configures the frontend class migration service to map old warehouse inventory level classes to the new ones.
 */
class InventoryLevelMigrationPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container)
    {
        $container
            ->getDefinition('oro_frontend.class_migration')
            ->addMethodCall(
                'append',
                ['WarehouseBundle\\Entity\\WarehouseInventoryLevel', 'InventoryBundle\\Entity\\InventoryLevel']
            )
            ->addMethodCall('append', ['oro_warehouse_inventory_lev', 'oro_inventory_level']);
    }
}
