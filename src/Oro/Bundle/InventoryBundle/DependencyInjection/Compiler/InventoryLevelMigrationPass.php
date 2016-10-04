<?php

namespace Oro\Bundle\InventoryBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class InventoryLevelMigrationPass implements CompilerPassInterface
{

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
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
