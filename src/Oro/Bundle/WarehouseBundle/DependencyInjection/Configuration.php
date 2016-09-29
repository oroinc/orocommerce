<?php

namespace Oro\Bundle\WarehouseBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class Configuration implements ConfigurationInterface
{
    const ENABLED_WAREHOUSES = 'enabled_warehouses';
    const MANAGE_INVENTORY = 'manage_inventory';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root(OroWarehouseExtension::ALIAS);
        SettingsBuilder::append(
            $rootNode,
            [
                self::ENABLED_WAREHOUSES => ['type' => 'array', 'value' => []],
                self::MANAGE_INVENTORY => ['type' => 'boolean', 'value' => false],
            ]
        );

        return $treeBuilder;
    }
}
