<?php

namespace Oro\Bundle\WarehouseBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class Configuration implements ConfigurationInterface
{
    const ENABLED_WAREHOUSES = 'enabled_warehouses';
    const MANAGE_INVENTORY = 'manage_inventory';
    const MINIMUM_QUANTITY_TO_ORDER = 'minimum_quantity_to_order';
    const MAXIMUM_QUANTITY_TO_ORDER = 'maximum_quantity_to_order';
    const DEFAULT_MAXIMUM_QUANTITY_TO_ORDER = 100000;

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
                self::MINIMUM_QUANTITY_TO_ORDER => ['type' => 'integer', 'value' => null],
                self::MAXIMUM_QUANTITY_TO_ORDER => [
                    'type' => 'integer',
                    'value' => self::DEFAULT_MAXIMUM_QUANTITY_TO_ORDER,
                ],
            ]
        );

        return $treeBuilder;
    }
}
