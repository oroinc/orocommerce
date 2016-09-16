<?php

namespace Oro\Bundle\WarehouseBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class Configuration implements ConfigurationInterface
{
    const ENABLED_WAREHOUSES = 'enabled_warehouses';

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
                'manage_inventory' => ['type' => 'boolean', 'value' => false],
            ]
        );

        return $treeBuilder;
    }
}
