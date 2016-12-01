<?php

namespace Oro\Bundle\InventoryBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class Configuration implements ConfigurationInterface
{
    const MANAGE_INVENTORY = 'manage_inventory';
    const INVENTORY_THRESHOLD = 'inventory_threshold';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root(OroInventoryExtension::ALIAS);
        SettingsBuilder::append(
            $rootNode,
            [
                self::MANAGE_INVENTORY => ['type' => 'boolean', 'value' => false],
                self::INVENTORY_THRESHOLD => ['type' => 'decimal', 'value' => 0],
            ]
        );

        return $treeBuilder;
    }
}
